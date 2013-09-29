<?php
namespace Gb\Model;


class Rows implements \IteratorAggregate, \Countable, \ArrayAccess {
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

    /**
     * Return the primary keys
     * @return array:
     */
    public function ids() {
        return $this->o;
    }

    /**
     * Return the rows as an associative array
     * @return array(id=>array(), ...)
     */
    public function asArray() {
        $model = $this->nam;
        return array_intersect_key($model::$_buffer, array_flip($this->o));
    }



    /**
     * @param string $relname
     * @return \Gb\Model\Rows
     */
    public function rel($relname) {
        $model = $this->nam;
        if (!isset($model::$rels[$relname])) {
            throw new \Gb_Exception("relation $relname does not exist for $model");
        }
        $relMeta = $model::$rels[$relname];
        $relclass = $relMeta["class_name"];
        $reltype  = $relMeta["reltype"];
        if ('belongs_to' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                // for each line, get the foreign key
                $relfks   = array_map(function($id)use($relfk, $model){return $model::$_buffer[$id][$relfk]; }, $this->o);
                $relfks   = array_unique($relfks);
                $relclass::_getSome($this->db, $relfks);
                $this->rel[$relname] = $relfks;
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        } elseif ('has_many' === $reltype) {
            // For all of our lines, find the other rows, referenced by our line
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                $relfks   = $this->o;
                $relat    = $relclass::findAll($this->db, array($relfk=>$relfks));
                $this->rel[$relname] = $relat->ids();
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        } elseif ('belongs_to_json' === $reltype) {
            if (!isset($this->rel[$relname])) {
                $relfk    = $relMeta["foreign_key"];
                $relfks   = array_map(function($id)use($relfk, $model){return $model::$_buffer[$id][$relfk]; }, $this->o);
                $relfks2  = array();
                array_walk($relfks, function($in) use (&$relfks2) {
                    $relfks2 = array_merge($relfks2, json_decode($in));
                });
                $relfks2 = array_unique($relfks2);
                $relclass::_getSome($this->db, $relfks2);
                $this->rel[$relname] = $relfks2;
            }
            return new Rows($this->db, $relclass, $this->rel[$relname]);
        }
    }




    // implements countable
    public function count() {
        return count($this->o);
    }

    // implements tostring
    public function __toString() {
        $r = "{\n";
        $first = 0;
        $model = $this->nam;
        foreach ($this->o as $k) {
            $r .= ($first++)?(",\n"):("");
            $r .= "  ";
            $r .= '"' . addslashes($k) . '":' . $model::_getOne($this->db, $k);
        }
        $r .= "\n}";
        return $r;
    }



    // implements StdClass
    public function __get($id) {
        if (!in_array($id, $this->o)) {
            throw new \Gb_Exception("row not found");
        }

        $aRels = array();
        $model = $this->nam;

        foreach($this->rel as $relname=>$reldata) {
            $relMeta = $model::$rels[$relname];
            $relclass = $relMeta["class_name"];
            $reltype  = $relMeta["reltype"];
            $relfk = $relMeta["foreign_key"];
            if ('belongs_to' === $reltype) {
                $pk = $model::$_buffer[$id][$relfk];
                $aRels[$relname] = $relclass::_getOne($this->db, $pk)->asArray();
            } elseif ('has_many' === $reltype) {
                $aRels[$relname] = array_filter($reldata, function($pk) use ($relclass, $relfk, $id) {
                    // keep only the matching lines
                    return $relclass::$_buffer[$pk][$relfk] == $id;
                });
            } elseif ('belongs_to_json' === $reltype) {
                // get the json values for the asked row
                $pks = json_decode($model::$_buffer[$id][$relfk]);
                $aRels[$relname] = $pks;
            }
        }

        return $model::_getOne($this->db, $id, $aRels);
    }
    public function __set($id, $value) {
        throw new \Gb_Exception("Not available");
    }
    public function __isset($id) {
        return in_array($id, $this->o);
    }
    public function __unset($id) {
        $this->o = array_filter($this->o, function($cur)use($id){return $id!==$cur;});
    }

    // Implements IteratorAggregate
    public function getIterator () {
        //return new \ArrayIterator($this->o);
        return new RowIterator($this->db, $this->nam, $this->o);
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

    // implements Iterator (actually it is not "implemented", superceded by IteratorAggregate, but these functions are handy)
    protected function tableRow($id) {
        $model = $this->nam;
        return $model::_getOne($this->db, $id);
    }
    public function current() {
        $id = current($this->o);
        return $this->tableRow($id);
    }
    public function next() {
        $id = next($this->o);
        if (false === $id) {
            return $id;
        }
        return $this->tableRow($id);
    }
    public function key() {
        return $this->o[key($this->o)];  // key must be the pk, not the array index
    }
    public function valid() {
        return key($this->o) !== null;

    }
    public function rewind () {
        return reset($this->o);
    }


}






class RowIterator implements \Iterator {
    protected $db;
    protected $o;
    protected $nam;

    public function __construct($db, $nam, $o) {
        $this->db = $db;
        $this->nam = $nam;
        $this->o = $o;
    }

    protected function tableRow($id) {
        $model = $this->nam;
        return $model::_getOne($this->db, $id);
    }

    // implements Iterator
    public function current() {
        $id = current($this->o);
        return $this->tableRow($id);
    }
    public function next() {
        $id = next($this->o);
        if (false === $id) {
            return $id;
        }
        return $this->tableRow($id);
    }
    public function key() {
        return $this->o[key($this->o)]; // key must be the pk, not the array index
    }
    public function valid() {
        return key($this->o) !== null;

    }
    public function rewind () {
        return reset($this->o);
    }

}
