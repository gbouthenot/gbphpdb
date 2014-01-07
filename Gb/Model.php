<?php
namespace Gb\Model;

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
} elseif (\_GB_PATH !== dirname(__FILE__).DIRECTORY_SEPARATOR) {
    throw new \Exception("gbphpdb roots mismatch");
}

require_once \_GB_PATH."Exception.php";
require_once \_GB_PATH."String.php";

require_once \_GB_PATH."Db.php";
require_once \_GB_PATH."Model/Rows.php";

class Model implements \IteratorAggregate, \ArrayAccess {

    /*********************/
    /**** STATIC PART ****/
    /*********************/

    /**
     * @var \Gb_Db the is the default adapter
     */
    protected static $_db;

    /**
     * whenever add created_at / updated_at timestamps
     * @var boolean
     */
    protected static $_timestamps = true;

    /**
     * Whenever all rows has been loaded
     * @var boolean
     */
    protected static $_isFullyLoaded = false;

    /**
     * Set the default adapter
     * @param \Gb_Db $db
     */
    public static function setAdapter(\Gb_Db $db) {
        self::$_db = $db;
    }

    /**
     * Get one row, by primary key
     * @param \Gb_Db[optional] $db
     * @param $id
     * @throws \Gb_Exception
     * @return \Gb\Model\Model
     */
    public static function getOne($id) {
        $args = func_get_args();
        $id = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        if (null === $id) {
            throw new \Gb_Exception("id is null");
        }

        if (!isset(static::$_buffer[$id])) {
            // fetch the row into the buffer
            self::fetch($db, $id);
        }

        return self::_getOne($db, $id);
    }

    public static function _getOne($db, $id, $rel=array()) {
        // send the row from the buffer
        $model = get_called_class();
        return new $model($db, $id, static::$_buffer[$id], $rel);
    }



    /**
     * Get some rows, by primary key
     * @param \Gb_Db[optional] $db
     * @param array $ids
     * @return \Gb\Model\Rows
     */
    public static function getSome($ids) {
        $args = func_get_args();
        $ids = array_unique(array_pop($args));
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        // fetch the rows
        self::_getSome($db, $ids);

        return new Rows($db, get_called_class(), $ids);
    }


    /*
     * Fetch the rows that are not in the buffer
     */
    public static function _getSome(\Gb_Db $db, array $ids) {
        // get the rows that are not in the buffer
        $ids = array_unique($ids);
        $fetchIds = array_diff($ids, array_keys(static::$_buffer));
        self::fetch($db, $fetchIds); // fetch them
    }


    /**
     * Get all rows
     * @param \Gb_Db[optional] $db
     * @return \Gb\Model\Rows
     */
    public static function getAll(\Gb_Db $db=null) {
        $args = func_get_args();
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        if (!static::$_isFullyLoaded) {
            self::fetch($db, null);
        }
        return new Rows($db, get_called_class(), array_keys(static::$_buffer));
    }



    /**
     * Fetch one or some or all rows from the database to the buffer. Will overwrite buffer rows.
     * @param \Gb_Db[optional] $db
     * @param mixed $ids single/array/null
     * @return null|\Gb\Model\Rows|\Gb\Model\Model
     * @throws \Gb_Exception if a row is not found for the asked keys.
     */
    public static function fetch($ids) {
        $args = func_get_args();
        $ids = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $sql  = " SELECT * FROM " . static::$_tablename;

        if (null !== $ids) {
            // force $ids to array
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            if (0 === count($ids)) {
                return;
            }
            // quote the key values
            $idsq = array_map(function($val)use($db){return $db->quote($val);}, $ids);

            $sql .= " WHERE " . static::$_pk . " IN (" . implode(",", $idsq) . ")";
        }

        $data = $db->retrieve_all($sql, null, static::$_pk, null, true);

        if (null === $ids) {
            static::$_isFullyLoaded = true;
        } elseif ( count($data) !== count($ids)) {
            if (1 === count($ids)) {
                throw new \Gb_Exception("row not found");
            }
            throw new \Gb_Exception(count($data) . " rows retrieved, " . count($ids) . " rows expected.");
        }

        // merge the rows in the buffer. Do not use array_merge!
        static::$_buffer += $data;

        $model = get_called_class();
        if (count($data) === 0) {
            return null;
        } elseif (count($data) === 1) {
            // get the first row
            $data = reset($data);
            $id = reset($ids);
            return new $model($db, $id, $data);
        } else {
            return new Rows($db, $model, array_keys($data));

        }
    }







    /**
     * Search lines
     * @param array|string[optional] $cond array("col"=>"value") or array("col"=>array(1,2)) or "col='value'"
     * @param array[optional] $options array("order"=>"cola DESC, colb", "limit"=>10, "offset"=>5)
     * @return \Gb\Model\Rows
     * @todo: if isFullyLoaded, handle the requestion without the database ?
     */
    public static function findAll($cond=null) {

        $args = func_get_args();
        $db = self::$_db;
        if (isset($args[0]) && $args[0] instanceof \Gb_Db) {
            $db = array_shift($args);
        }
        $cond = array_shift($args);
        $options = array_shift($args); if (null===$options){$options=array();}

        $sql = static::_find($db, $cond, $options);

        $data = $db->retrieve_all($sql, null, static::$_pk, null, true);

        //echo static::$_tablename."\n";print_r(static::$_buffer);
        // merge the rows in the buffer. Do not use array_merge!
        static::$_buffer += $data;

        $model = get_called_class();
        return new Rows($db, $model, array_keys($data));
    }



    /**
     * return the first line
     * @param array|string[optional] $cond array("col"=>"value") or array("col"=>array(1,2)) or "col='value'"
     * @return null|\Gb\Model\Model
     */
    public static function findFirst($cond=null) {
        $args = func_get_args();
        $cond = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $sql = static::_find($db, $cond);
        $sql .= " LIMIT 1";

        $data = $db->retrieve_one($sql);
        // merge the row in the buffer.
        $id = $data[static::$_pk];
        if ($id !== null) {
            static::$_buffer[$id] = $data;

            $model = get_called_class();
            return new $model($db, $id, $data);
        } else {
            return null;
        }
    }


    /**
     * Return the sql for searching
     * @param \Gb_Db $db
     * @param array|string[optional] $cond array("col"=>"value") or array("col"=>array(1,2)) or "col='value'"
     * @param array[optional] $options array("order"=>"cola DESC, colb", "limit"=>10, "offset"=>5)
     * @return string
     */
    protected static function _find(\Gb_db $db, $cond, $options=array()) {
        $tablename = static::$_tablename;
        $sql  = " SELECT * FROM $tablename";
        if (is_array($cond) && count($cond)) {
            $aWhere = array();
            foreach ($cond as $k=>$v) {
                if (is_array($v)) {
                    $aWhere[] = $db->quoteIdentifier($k) . ' IN (' . $db->quote($v) . ')';
                } elseif (null === $v) {
                    $aWhere[] = $db->quoteIdentifier($k) . ' IS NULL';
                } elseif (is_a($v, "Zend_Db_Expr")) {
                    $aWhere[] = $db->quoteIdentifier($k) . " $v";
                } else {
                    $aWhere[] = $db->quoteIdentifier($k) . '=' . $db->quote($v);
                }
            }
            $sql .= " WHERE " . join(" AND ", $aWhere);
        } elseif (is_string($cond) && strlen($cond)) {
            $sql .= " WHERE $cond";
        }

        if (isset($options["order"])) {
            $sql.= " ORDER BY " . $options["order"];
        }
        if (isset($options["limit"])) {
            $sql.= " LIMIT " . $options["limit"];
            if (isset($options["offset"])) {
                $sql.= " OFFSET " . $options["offset"];
            }
        }

        return $sql;
    }

    /**
     * returns a blank row. created_at is handled by save()
     * @return \Gb\Model\Model
     */
    public static function create(/* $db=null, $data=null*/) {
        $args = func_get_args();
        $data = array_pop($args);
        $db = array_pop($args);
        if (is_array($db)) {
            $data = $db;
            $db = null;
        }

        if ($db   === null) { $db = self::$_db; }
        if ($data === null) { $data = array(); }

        if (!is_a($db, "Gb_Db")) {
            throw new \Gb_Exception("database should be an instance of Gb_Db " . get_class($db) . " given.");
        }

        $model = get_called_class();
        return new $model($db, null, $data);
    }











    /***********************/
    /**** INSTANCE PART ****/
    /***********************/



    /**
     * @var \Gb_Db
     */
    protected $db;
    /**
     * @var mixed the primary key value. Used for save()
     */
    protected $id;
    /**
     * @var array the data
     */
    protected $o;
    /**
     * @var array the relations
     */
    protected $rel;

    public function __construct(\Gb_Db $db, $id, array $data, array $rel=array()) {
        $this->db   = $db;
        $this->id   = $id;
        $this->o    = $data;
        $this->rel  = $rel;
    }

    public function asArray() {
        return $this->o;
    }
    public function asJson() {
        return json_encode($this->o);
    }

    /**
     * Save the row
     * handles updated_at and created_at
     * @return \Gb\Model\Model
     */
    public function save() {
        $table = static::$_tablename;
        $pk    = static::$_pk;
        $db    = $this->db;
        $id    = $this->id;
        if (static::$_timestamps) {
            $this->o["updated_at"] = \Gb_String::date_iso();
            if (null === $id) {
                $this->o["created_at"] = $this->o["updated_at"];
            }
        }
        if (null === $id) {
            $db->insert($table, $this->o);
            $id = $db->lastInsertId();
            $this->id = $id;
            $this->o[$pk] = $id;
        } else {
            $db->update($table, $this->o, $db->quoteInto("$pk = ?", $id));
        }

        // save in the buffer
        static::$_buffer[$id] = $this->o;

        return $this;
    }

    /**
     * Delete the row
     * The data is still in memory
     * @return \Gb\Model\Model
     */
    public function destroy() {
        $table = static::$_tablename;
        $pk    = static::$_pk;
        $db    = $this->db;
        $id    = $this->id;
        if (null !== $id) {
            $db->delete($table, $db->quoteInto("$pk = ?", $id));
            // remove from the buffer
            unset(static::$_buffer[$id]);
        }
        // after deletion, the data remain in memory, and can be inserted again upon save()
        $this->id = null;

        return $this;
    }


	/* (non-PHPdoc)
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator () {
        return new \ArrayIterator($this->o);
    }

    public function __get($key) {
        if (isset($this->o[$key])) {
            return $this->o[$key];
        } else {
            return null;
        }
    }
    public function __set($key, $value) {
        $this->o[$key] = $value;
    }
    public function __isset($key) {
        return isset($this->o[$key]);
    }
    public function __unset($key) {
        unset($this->o[$key]);
    }

    // implements ArrayAccess
    public function offsetSet($key, $value) {
        return $this->__set($key, $value);
    }
    public function offsetExists($key) {
        return $this->__isset($key);
    }
    public function offsetUnset($key) {
        return $this->__unset($key);
    }
    public function offsetGet($key) {
        return $this->__get($key);
    }

    public function __toString() {
        return json_encode($this->o);
    }

    public function rel($relname) {
        $model = get_called_class();
        if (!isset($model::$rels[$relname])) {
            throw new \Gb_Exception("relation $relname does not exist for $model");
        }
        $relMeta = $model::$rels[$relname];
        $relclass = $relMeta["class_name"];
        if (!class_exists($relclass, true)) {
            throw new \Gb_Exception("Class $relclass does not exist for $model -> $relname");
        }
        $reltype  = $relMeta["reltype"];
        if ('belongs_to' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
            if (!isset($this->o[$relfk])) { throw new \Gb_Exception("Relation {$model}->rel('{$relname}'): there is no column $relfk"); }
                $relfk    = $this->o[$relfk];
                $relat    = $relclass::getOne($this->db, $relfk);
                $this->rel[$relname] = $relat->asArray();
            }
            return new $relclass($this->db, $this->rel[$relname][$relclass::$_pk], $this->rel[$relname]);
        } elseif ('has_many' === $reltype) {
            if (!isset($this->rel[$relname])) {
                // Find the other rows referenced by our line
                $relfk    = $relMeta["foreign_key"];
                $relat    = $relclass::findAll($this->db, array($relfk=>$this->o[$relclass::$_pk]));
                $this->rel[$relname] = $relat->ids();
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        } elseif ('belongs_to_json' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                if (!isset($this->o[$relfk])) { throw new \Gb_Exception("Relation {$model}->rel('{$relname}'): there is no column $relfk"); }
                $relfk    = $this->o[$relfk];
                $relfk    = json_decode($relfk);
                $relclass::_getSome($this->db, $relfk);
                $this->rel[$relname] = $relfk;
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        }
    }

}

