<?
/**
 * Gb_Args
 * 
 * @author Gilles Bouthenot
 * @version $Revision: 112 $
 * @Id $Id: File.php 112 2008-08-27 14:48:17Z gbouthenot $
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}



Class Gb_Args
{
    protected $_args;
    
    public function __construct(array $params=array())
    {
        $this->_args=$params;
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
    
}
