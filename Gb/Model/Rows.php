<?php
namespace Gb\Model;

class Rows implements \Iterator, \Countable, \ArrayAccess {
    /**
     * @var Gb_Db
     */
    protected $db;
    /**
     * @var string
     */
    protected $nam;
    /**
     * @var array
     */
    protected $o;
    /**
     * @var array
     */
    protected $rel;

    public function __construct(\Gb_Db $db, $classname, array $data, $rel=array()) {
        $this->db   = $db;
        $this->nam  = $classname;
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
        if (!isset($this->o[$key])) {
            throw new \Gb_Exception("row not found");
        }

        $data = $this->o[$key];

        $aRels = array();
        $class = $this->nam;

        foreach($this->rel as $relname=>$reldata) {
            $relMeta = $class::$rels[$relname];
            $reltype  = $relMeta["reltype"];
            $relfk = $relMeta["foreign_key"];
            if ('belongs_to' === $reltype) {
                $aRels[$relname] = $reldata[$data[$relfk]];
            } elseif ('has_many' === $reltype) {
                $aRels[$relname] = array_filter($reldata,function($row)use($relfk, $key){return $key == $row[$relfk];});
            }
        }

        return new Row($this->db, $this->nam, $data["id"], $data, $aRels);
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
        $r = "{\n";
        $first = 0;
        foreach ($this->o as $k=>$v) {
            $r .= ($first++)?(",\n"):("");
            $r .= "  ";
            $r .= '"' . addslashes($k) . '":' . json_encode($v);
        }
        $r .= "\n}";
        return $r;
    }

    public function count() {
        return count($this->o);
    }


    protected function tableRow(array $data) {
        return new Row($this->db, $this->nam, $data["id"], $data);
    }

    public function current() {
        $data = current($this->o);
        return $this->tableRow($data);
    }
    public function next() {
        $data = next($this->o);
        if (false === $data) {
            return $data;
        }
        return $this->tableRow($data);
    }
    public function key() {
        return key($this->o);
    }
    public function valid() {
        return key($this->o) !== null;

    }
    public function rewind () {
        return reset($this->o);
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

    /**
     * @param string $relname
     * @return \Gb\Model\Rows
     */
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
                $relfks   = array_unique(array_map(function($row)use($relfk){return $row[$relfk]; }, $this->o));
                $relat    = $relclass::getSome($this->db, $relfks)->data();
                $this->rel[$relname] = $relat;
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        } elseif ('has_many' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                $relfks   = array_keys($this->o);
                $relat    = $relclass::findAll($this->db, array($relfk=>$relfks))->data();
                $this->rel[$relname] = $relat;
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        }
    }
}

?>