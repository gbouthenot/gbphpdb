<?php
/**
 * Gb_Args
 *
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== dirname(__FILE__).DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}

require_once(_GB_PATH."Exception.php");


Class Gb_Args
{
    protected $_args;

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision$';
        $revision=(int) trim(substr($revision, strrpos($revision, ":")+2, -1));
        if ($mini===null) { return $revision; }
        if ($revision>=$mini) { return true; }
        if ($throw) { throw new Gb_Exception(__CLASS__." r".$revision."<r".$mini); }
        return false;
    }


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
        } elseif ($params instanceof Gb_Args ) {
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

    public function prepend($name, $value=null)
    {
        $args2=array();
        if ($value===null) {
            $args2[]=$name;
        } else {
            $args2[$name]=$value;
        }
        foreach ($this->_args as $k=>$v) {
            if (is_integer($k)) {
                $k++;
            }
            $args2[$k]=$v;
        }
        $this->_args=$args2;
        return $this;
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

    /**
     * replace the args by those specified
     * @param Gb_Args|array $args2
     * @returns Gb_Args
     */
    public function replace($args2)
    {
        $args3=new Gb_Args($args2);
        $this->_args=$args3->getAll();
        return $this;
    }
}
