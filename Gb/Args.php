<?
/**
 * Gb_Args
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}



Class Gb_Args
{
    protected $_args;
    
    /**
     * @param array|string|Gb_Args[optional]  $params
     */
    public function __construct($params=array())
    {
        if (is_array($params)) {
            $this->_args=$params;
        } elseif (is_string($params)) {
            $args=explode("/", $params);
            if (count($args)==1 && $args[0]=="") {
                $args=array();
            }
            $this->_args=$args;
        } elseif ($args instanceof Gb_Args ) {
            $this->_args=$params->getAll();
        } else {
            throw new Gb_Exception("invalid params given");
        }
    }
    
    
    public function get($name=null)
    {
        if ($name===null) {
            if (isset($this->_args[0])) {
                return $this->_args[0];
            } else {
                return null;
            }
        }
        
        $num=count($this->_args);
        for ($i=0; $i<$num-1; $i++) {
            $current=$this->_args[$i];
            if ($current==$name) {
                return $this->_args[$i+1];
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->_args;
    }

    public function remove($name=null)
    {
        if ($name===null) {
            if (isset($this->_args[0])) {
                return array_shift($this->_args);
            } else {
                return null;
            }
        }
        
        $value=null;
        $num=count($this->_args);
        $args2=array();
        for ($i=0; $i<$num-1; $i++) {
            $current=$this->_args[$i];
            if ($current==$name) {
                $value=$this->_args[$i+1];
                $i++; //skip the value
            } else {
                $args2[]=$current;
            }
        }
        return $value;
    }
    
    /**
     * @return array
     */
    public function removeAll()
    {
        $all=$this->_args;
        $this->_args=array();
        return $all;
    }
}
