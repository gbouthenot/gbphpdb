<?php

namespace Gb\Model;

class Row implements \IteratorAggregate {
    /**
     * @var Gb_Db
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
        }
    }

}

?>