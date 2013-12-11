<?php
/**
 * Gb_Form_Iterator
 *
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");


class Gb_Form_Iterator implements RecursiveIterator, Countable
{
    protected $_parent;
    protected $_keys;

    public function __construct($parent)
    {
        $this->_parent=$parent;
        $this->_keys=$parent->getKeys();
    }

// implements Iterator START
    public function current()
    {
        $key=current($this->_keys);
        if ($key===null || $key==="" || $key===false) { return null; }
        return $this->_parent->$key;
    }
    public function valid()
    {
        return array_key_exists(key($this->_keys), $this->_keys);
    }
    public function key()
    {
        return current($this->_keys);
    }
    public function next()
    {
        next($this->_keys);
    }
    public function rewind()
    {
        reset($this->_keys);
    }
// implements Iterator END

// implements RecursiveIterator START
    public function hasChildren()
    {
        $key=current($this->_keys);
        return method_exists($this->_parent->$key, "getIterator");
    }
    public function getChildren()
    {
        $key=current($this->_keys);
        return $this->_parent->$key->getIterator();
    }
// implements RecursiveIterator END

// implements Countable START
    public function count()
    {
        return count($this->_keys);
    }
// implements Countable END

}
