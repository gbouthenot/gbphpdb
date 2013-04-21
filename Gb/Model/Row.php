<?php

namespace Gb\Model;

require_once "Gb/String.php";

class Row implements \IteratorAggregate, \ArrayAccess {
    /**
     * @var \Gb_Db
     */
    protected $db;
    /**
     * @var string
     */
    protected $nam;
    protected $id;
    /**
     * @var array
     */
    protected $o;
    /**
     * @var array
     */
    protected $rel;

    public function __construct(\Gb_Db $db, $classname, $id, array $data, array $rel=array()) {
        $this->db   = $db;
        $this->nam  = $classname;
        $this->id   = $id;
        $this->o    = $data;
        $this->rel  = $rel;
    }

    public function data() {
        return $this->o;
    }

    public function save() {
        $nam   = $this->nam;
        $table = $nam::$_tablename;
        $pk    = $nam::$_pk;
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
        $nam   = $this->nam;
        $table = $nam::$_tablename;
        $pk    = $nam::$_pk;
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
        $class = $this->nam;
        if (!isset($class::$rels[$relname])) {
            throw new \Gb_Exception("relation $relname does not exist for $class");
        }
        $relMeta = $class::$rels[$relname];
        $relclass = $relMeta["class_name"];
        $reltype  = $relMeta["reltype"];
        if ('belongs_to' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                $relfk    = $this->o[$relfk];
                $relat    = $relclass::getOne($this->db, $relfk);
                $this->rel[$relname] = $relat->data();
            }
            return new Row($this->db, $relclass, $this->rel[$relname]["id"], $this->rel[$relname]);
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

?>