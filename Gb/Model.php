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
     * @return self
     */
    public static function getOne($id) {
        $args = func_get_args();
        $id = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        if (null === $id) {
            throw new \Gb_Exception("id is null");
        }
        $id = (int) $id;

        if (!isset(static::$_buffer[$id])) {
            // fetch the row into the buffer
            self::_fetch($db, $id);
        }

        return self::_getOne($db, $id);
    }

    public static function _getOne($db, $id, $rel=array()) {
        $id = (int) $id;
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
        self::_fetch($db, $fetchIds); // fetch them
    }


    /**
     * Get all rows
     * @param \Gb_Db[optional] $db
     * @return \Gb\Model\Rows
     */
    public static function getAll() {
        $args = func_get_args();
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        if (!static::$_isFullyLoaded) {
            self::_fetch($db, null);
        }
        return new Rows($db, get_called_class(), array_keys(static::$_buffer));
    }



    /**
     * Fetch one or some or all rows from the database to the buffer. Overwrite buffer rows.
     * @param \Gb_Db[optional] $db
     * @param mixed $ids single/array/null
     * @throws \Gb_Exception if a row is not found for the asked keys.
     */
    public static function _fetch($ids) {
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
            // id should be integer
            $idsq = array_map(function($val){return (int) $val;}, $ids);

            $sql .= " WHERE " . static::$_pk . " IN (" . implode(",", $idsq) . ")";
        }

        $data = $db->retrieve_all($sql, null, static::$_pk, null, true);
        $data = self::castInteger_all($data);

        if (null === $ids) {
            static::$_isFullyLoaded = true;
        } elseif ( count($data) !== count($ids)) {
            if (1 === count($ids)) {
                throw new \Gb_Exception("row not found");
            }
            throw new \Gb_Exception(count($data) . " rows retrieved, " . count($ids) . " rows expected.");
        }

        // merge the rows in the buffer. Do not use array_merge!
        static::$_buffer = $data + static::$_buffer;

        return null;
        /*
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
        */
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
        $data = self::castInteger_all($data);

        //echo static::$_tablename."\n";print_r(static::$_buffer);
        // merge the rows in the buffer. Do not use array_merge!
        static::$_buffer = $data + static::$_buffer;

        $model = get_called_class();
        return new Rows($db, $model, array_keys($data));
    }



    /**
     * return the first line
     * @param array|string[optional] $cond array("col"=>"value") or array("col"=>array(1,2)) or "col='value'"
     * @see findFirstOrThrows
     * @return null|\Gb\Model\Model
     */
    public static function findFirst($cond=null) {
        $args = func_get_args();
        $cond = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $sql = static::_find($db, $cond);
        $sql .= " LIMIT 1";

        $data = $db->retrieve_one($sql);
        if ($data === false) {
            return null;
        }
        $data = self::castInteger_one($data);

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
     * return the first line or throw an exception
     * @param array|string[optional] $cond array("col"=>"value") or array("col"=>array(1,2)) or "col='value'"
     * @see findFirst
     * @return null|\Gb\Model\Model
     */
    public static function findFirstOrThrows($cond=null) {
        $z = "Row not found for Model " . get_called_class();
        $ret = self::findFirst($cond);
        if (null === $ret) {
            throw new \Gb_Exception("Row not found for Model " . get_called_class());
        }
        return $ret;
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
                if (is_string($k)) {
                    if (is_array($v)) {
                        $aWhere[] = $db->quoteIdentifier($k) . ' IN (' . $db->quote($v) . ')';
                    } elseif (null === $v) {
                        $aWhere[] = $db->quoteIdentifier($k) . ' IS NULL';
                    } elseif (is_a($v, "Zend_Db_Expr")) {
                        $aWhere[] = $db->quoteIdentifier($k) . " $v";
                    } else {
                        $aWhere[] = $db->quoteIdentifier($k) . '=' . $db->quote($v);
                    }
                } elseif (is_int($k)) {
                    if (is_a($v, "Zend_Db_Expr")) {
                        $aWhere[] = $v->__toString();
                    }
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
     * @return self
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
        $obj = new $model($db, null, array());
        $obj->set($data);
        return $obj;
    }





    /**
     * Cast columns to integer
     * @param array[array] $data
     * @return array[array]
     */
    protected static function castInteger_all($data) {
        $integerCols = self::_getIntegerCols();

        foreach (array_keys($data) as $rowid) {
            foreach ($integerCols as $col) {
                if (isset($data[$rowid][$col]) && (null !== $data[$rowid][$col])) {
                    $data[$rowid][$col] = (int) $data[$rowid][$col];
                }
            }
        }

        return $data;
    }


    /**
     * Cast columns to integer
     * @param array $data
     * @return array
     */
    protected static function castInteger_one($data) {
        if (is_array($data)) {
            $data = self::castInteger_all(array(0=>$data));
            $data = $data[0];
        }
        return $data;
    }





    /**
     * return array of column to be casted to int
     * @return array
     */
    protected static function _getIntegerCols() {
        if (isset(static::$_integerCols)) {
            $integerCols = static::$_integerCols;
        } else {
            $integerCols = array();
        }
        $integerCols[] = static::$_pk; // always push id

        return $integerCols;
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
     * @var array initial data, as fetched from db
     */
    protected $pristine = null;
    /**
     * @var array the relations
     */
    protected $rel;

    public function __construct(\Gb_Db $db, $id, array $data, array $rel=array()) {
        $this->db   = $db;
        $this->id   = $id;
        $this->o    = $data;
        $this->pristine = $data;
        $this->rel  = $rel;
    }

    public function asArray() {
        return $this->o;
    }
    public function asJson() {
        return json_encode($this->o);
    }



    /**
     * Save the row, if modified
     * handles updated_at and created_at
     * @return self
     */
    public function save() {
        if ($this->isModified()) {
            $this->saveAlways();
        }

        return $this;
    }



    /**
     * Save the row, even if not modified
     * handles updated_at and created_at
     * @return self
     */
    public function saveAlways() {
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
            $id = (int) $db->lastInsertId();
            $this->id = $id;
            $this->o[$pk] = $id;
        } else {
            $id    = (int) $this->id;
            $db->update($table, $this->o, "$pk = $id");
        }

        // reload the row into the buffer
        $this->_fetch($id);

        return $this;
    }


    /**
     * Set the properties if the column exists
     * @see set
     * @param array $data
     */
    public function merge(array $data) {
        foreach ($data as $key=>$value) {
            if ($this->__isset($key)) {
                $this->__set($key, $value);
            }
        }
    }
    /**
     * Set properties. Even if the column does not exist.
     * @see merge
     * @param array $data
     */
    public function set(array $data) {
        foreach ($data as $key=>$value) {
            $this->__set($key, $value);
        }
    }



    /**
     * The model has been modified ?
     * @return boolean
     */
    public function isModified() {
        // == return true regardless of key order, and value type
        // $a=array("a1"=>"1", "a2"=>"2");  $b=array("a2"=>2, "a1"=>1);  $a == $b --> true
        // UNFORTUNATELY: $a=array("a1"=>0, "a2"=>"2");  $b=array("a2"=>2, "a1"=>null);  $a == $b --> true !!! 0 == null :-(
        // so I return always true for the time being
        //return $this->o != $this->pristine;
        return true;
    }

    /**
     * Delete the row
     * The data is still in the model instance
     * @return self
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
        // after deletion, the data remain in the instance memory, and can be inserted again upon save()
        $this->id = null;
        $this->pristine = null;


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
        if (substr($key, 0, 3) === "is_") {
            if ($value===false || $value==="false" || $value===0 || $value==="0") {
                $value = "0";
            } elseif ($value===true || $value==="true" || $value===1 || $value==="1") {
                $value = "1";
            } else {
                throw new \Gb_Exception("$value is not boolean for column $key");
            }
        } elseif ($key === static::$_pk) {
            if (strlen($value) & ((int)$value)>0) {
                $value = (int) $value;
            } else {
               $value = null;
            }
            if ($value !== $this->id) {
                if ($value === null) {
                    return;
                } else {
                    throw new \Gb_Exception("Changing primary key is not supported value:$value id:{$this->id}");
                    // do not allow id change $value = (int) $value;
                    // cause: the old row would still be in the buffer, so this would only be a duplicate row

                    //$this->id = $value;
                    //$this->pristine = array(); // mark dirty
                }
            }
        }
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
        } elseif ('has_many_through' === $reltype) {
            if (!isset($this->rel[$relname])) {
                // Find the other rows referenced by our line
                $relfk    = $relMeta["foreign_key"];
                $through  = $relMeta["through"];
                $pivClass = $through[0];
                $pivCol   = $through[1];
                $pivfk    = $this->o[$relclass::$_pk];
                //echo "<br />relfk:$relfk // value:$pivfk // relclass:$relclass // pivClass:$pivClass // pivCol:$pivCol<br />";
                $pivot = $pivClass::findAll($this->db, array($pivCol=>$pivfk));
                //echo "<br />pivot: $pivot<br />";
                $relfks   = $pivot->pluck($relfk);
                $this->rel[$relname] = $relfks;
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
        } else {
            throw new \Gb_Exception("Unsupported relation type $reltype for model $model");
        }
    }

}

