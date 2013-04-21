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

    /**
     * @var \Gb_Db
     */
    protected static $_db;

    public static function setAdapter(\Gb_Db $db) {
        self::$_db = $db;
    }

    public static function getOne($id) {
        $args = func_get_args();
        $id = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $tablename = static::$_tablename;
        $sql = "SELECT * FROM $tablename WHERE " . static::$_pk . " = ?";
        $data = $db->retrieve_one($sql, $id);
        if (!$data) {
            throw new \Gb_Exception("row not found");
        }
        $model = get_called_class();
        return new $model($db, $id, $data);
    }

    /**
     * @param Gb_Db[optional] $db
     * @param array $ids
     * @return \Gb\Model\Rows
     */
    public static function getSome($ids) {
        $args = func_get_args();
        $ids = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $idsq = array_map(function($val)use($db){return $db->quote($val);}, $ids);

        $tablename = static::$_tablename;
        $sql  = " SELECT * FROM $tablename";
        $sql .= " WHERE " . static::$_pk . " IN (" . implode(",", $idsq) . ")";
        $data = $db->retrieve_all($sql, null, static::$_pk, null, true);
        return new Rows($db, get_called_class(), $data);
    }


    public static function getAll() {
        $args = func_get_args();
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $tablename = static::$_tablename;
        $sql  = " SELECT * FROM $tablename";
        $data = $db->retrieve_all($sql, null, static::$_pk, null, true);
        return new Rows($db, get_called_class(), $data);
    }

    public static function findAll($cond) {
        $args = func_get_args();
        $cond = array_pop($args);
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $tablename = static::$_tablename;
        $sql  = " SELECT * FROM $tablename";
        if (count($cond)) {
            $aWhere = array();
            foreach ($cond as $k=>$v) {
                if (is_array($v)) {
                    $aWhere[] = $db->quoteIdentifier($k) . ' IN (' . $db->quote($v) . ')';
                } else {
                    $aWhere[] = $db->quoteIdentifier($k) . '=' . $db->quote($v);
                }
            }
            $sql .= " WHERE " . join(" AND ", $aWhere);
        }
        $data = $db->retrieve_all($sql, null, static::$_pk, null, true);
        return new Rows($db, get_called_class(), $data);
    }

    public static function create() {
        $args = func_get_args();
        $db = array_pop($args); if (!$db) {$db = self::$_db; };

        $model = get_called_class();
        return new $model($db, null, array());
    }



/*
    public function __construct($tablename, $db) {
        $this->_tablename = $tablename;
        $this->_db = $db;
    }

    public function getById($id) {
        if (is_array($id)) {
            $sql = "SELECT * from {$this->_tablename} WHERE id IN ";
            $aVals = array();
            foreach($id as $i) {
                $aVals[] = $this->_db->quote($i);
            }
            $sql .= "(" . join(",", $aVals). ")";
            $res = $this->_db->retrieve_all($sql, null, "id", null, true);
            return $res;
        }
        $sql = "select * from {$this->_tablename} where id = ?";
        $res = $this->_db->retrieve_one($sql, $id);
        return $res;
        //return $this->unjson_one($res);
    }

    public function length() {
        $sql = "select count(*) as 'A' from {$this->_tablename}";
        $res = $this->_db->retrieve_one($sql, null, 'A');
        return $res;
    }

    public function search($aPar=null) {
        $sql = $this->_search($aPar);
        $res = $this->_db->retrieve_all($sql);
        return $res;
        //return $this->unjson_all($res);
    }

    public function searchFirst($aPar = null) {
        $sql = $this->_search($aPar);
        $sql .= " LIMIT 1";
        $res = $this->_db->retrieve_one($sql);
        return $res;
        //return $this->unjson_one($res);
    }

    protected function _search($aPar=null) {
        if (null === $aPar) {
            $aPar = array();
        }
        $sql = "select * from {$this->_tablename}";
        $aWhere = array();
        foreach ($aPar as $ind=>$val) {
            $aWhere[] = $this->_db->quoteInto($ind.'=?', $val);
        }
        if (count($aWhere)) {
            $sql .= " WHERE " . join(" AND ", $aWhere);
        }
        return $sql;
    }

    protected function unjson_one($a) {
        foreach($a as $col=>$val) {
            if ('_json' === substr($col, -5)) {
                $a[$col] = json_decode($val);
            }
        }
        return $a;
    }

    protected function unjson_all($rows) {
        if (!(is_array($rows) && count($rows)) ) {
            return;
        }
        $firstrow = array_keys($rows);
        $firstrow = array_keys($rows[$firstrow[0]]);
        foreach ($firstrow as $col) {
            if ('_json' === substr($col, -5)) {
                foreach ($rows as $rowid=>$row) {
                    $rows[$rowid][$col] = json_decode($row[$col]);
                }
            }

        }
        return $rows;
    }

    public function adapter() {
        return $this->_db;
    }

    public function tablename() {
        return $this->_tablename;
    }

*/














    /**
     * @var \Gb_Db
     */
    protected $db;
    /**
     * @var mixed the primary key value. Used for save()
     */
    protected $id;
    /**
     * @var array
     */
    protected $o;
    /**
     * @var array
     */
    protected $rel;

    public function __construct(\Gb_Db $db, $id, array $data, array $rel=array()) {
        $this->db   = $db;
        $this->id   = $id;
        $this->o    = $data;
        $this->rel  = $rel;
    }

    public function data() {
        return $this->o;
    }

    public function save() {
        $table = static::$_tablename;
        $pk    = static::$_pk;
        $db    = $this->db;
        $id    = $this->id;
        $this->o["updated_at"] = \Gb_String::date_iso();
        if (null === $id) {
            $this->o["created_at"] = $this->o["updated_at"];
            $db->insert($table, $this->o);
            $this->id = $db->lastInsertId();
        } else {
            $db->update($table, $this->o, $db->quoteInto("$pk = ?", $id));
        }
    }

    public function destroy() {
        $table = static::$_tablename;
        $pk    = static::$_pk;
        $db    = $this->db;
        $id    = $this->id;
        if (null !== $id) {
            $db->delete($table, $db->quoteInto("$pk = ?", $id));
        }
        // after deletion, the data remain in memory, and can be inserted again upon save()
        $this->id = null;
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
        $reltype  = $relMeta["reltype"];
        if ('belongs_to' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                $relfk    = $this->o[$relfk];
                $relat    = $relclass::getOne($this->db, $relfk);
                $this->rel[$relname] = $relat->data();
            }
            return new $relclass($this->db, $this->rel[$relname]["id"], $this->rel[$relname]);
        } elseif ('has_many' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                $relat    = $relclass::findAll($this->db, array($relfk=>$this->o["id"]));
                $this->rel[$relname] = $relat->data();
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        } elseif ('belongs_to_json' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                $relfk    = $this->o[$relfk];
                $relfk    = json_decode($relfk);
                $relat    = $relclass::getSome($this->db, $relfk);
                $this->rel[$relname] = $relat->data();
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        }
    }

}

