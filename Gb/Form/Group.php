<?php
require_once("Iterator.php");
require_once("Elem.php");
require_once("Elem/Text/Abstract.php");
require_once("Elem/Text.php");
require_once("Elem/Password.php");
require_once("Elem/Hidden.php");


class Gb_Form_Group implements IteratorAggregate
{
    protected $_elems;
    protected $_modifiers;

    protected $_toStringRendersAs="HTML";
    protected $_preGroup;
    protected $_postGroup;

    /**
     * Constructeur de Gb_Form_Group
     *
     * @param array[optional] $aParams
     * @param array[optional] $modifiers : arguments qui modifiront les paramètres des enfants de ce groupe
     */
    public function __construct($aParams=array(), $modifiers=array())
    {
       $this->_elems=array();
       $this->_modifiers=$modifiers;
       
       $availableParams=array(
            "toStringRendersAs", "preGroup", "postGroup",
        );
        
        foreach ($availableParams as $key) {
            if (isset($aParams[$key])) {
                $val=$aParams[$key];
            } else {
                $val=null;
            }
            call_user_func(array($this, $key), $val);
        }
    }
    
// implements InteratorAggregate START
    public function getIterator()
    {
        return new Gb_Form_Iterator($this);
    }
// implements InteratorAggregate END

    final private function _applyModifiers($obj)
    {
        $c=get_class($obj);
        if ($c=="Gb_Form_Group") {
            foreach ($obj as $objchild) {
                $this->_applyModifiers($objchild);
            }
        } else {
            foreach ($this->_modifiers as $key=>$str) {
                if (method_exists($obj, $key)) {
                    // lit la valeur actuelle
                    $arg=call_user_func(array($obj, $key));
                    $arg=sprintf($str, $arg);
                    // ecrit la nouvelle valeur
                    call_user_func(array($obj, $key), $arg);
                }
            }
        }
        return $obj;
    }
    
// implements standard OOP START
    final public function __set($key, $obj)
    {
        $obj=$this->_applyModifiers($obj);
        $this->_elems[$key]=$obj;
        return $this;
    }
    final public function __get($key)
    {
        if ($this->__isset($key)) {
            return $this->_elems[$key];
        } else {
            throw new Gb_Exception("element ".serialize($key)." inexistant");
        }
    }
    final public function __isset($key)
    {
        return isset($this->_elems[$key]);
    }
    final public function __unset($key)
    {
        if ($this->__isset($key)) {
            unset($this->_elems[$key]);
            return $this;
        } else {
            throw new Gb_Exception("element $key inexistant");
        }
        
    }
// implements standard OOP END
    
    final public function append()
    {
        $args=func_get_args();
        foreach ($args as $arg) {
            $this->_elems[]=$this->_applyModifiers($arg);
        }
        return $this;
    }

    final public function getKeys()
    {
        return array_keys($this->_elems);
    }
    
    final public function __toString()
    {
        return $this->render();
    }
    final public function renderHtml()
    {
        $ret=$this->preGroup();
        foreach ($this as $elem) {
            if ($elem instanceof Gb_Form_Elem || $elem instanceof Gb_Form_Group) {
                $ret.=$elem->renderHtml();
            }
        }
        $ret.=$this->postGroup();
        $ret.="\n";
        return $ret;
    }
    final public function renderJavascript()
    {
        $ret="";
        foreach ($this as $elem) {
            if ($elem instanceof Gb_Form_Elem || $elem instanceOf Gb_Form_Group) {
                $ret.=$elem->renderJavascript();
            }
        }
        return $ret;
    }
    final public function render()
    {   $ret="";
        if ($this->_toStringRendersAs=="HTML") {
            $ret=$this->renderHtml();
        } elseif ($this->_toStringRendersAs=="JS") {
            $ret=$this->renderJavascript();
        }
        return $ret;
    }

    /**
     * get/set preGroup
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function preGroup($text=null)
    {   
        if ($text===null) {         return $this->_preGroup; }
        else { $this->_preGroup=$text; return $this;}
    }
    /**
     * get/set postGroup
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function postGroup($text=null)
    {   
        if ($text===null) {         return $this->_postGroup; }
        else { $this->_postGroup=$text; return $this;}
    }
    /**
     * Set the type of data returned by __toString()
     *
     * @param string $type "HTML" or "JS"
     * @return Gb_Form_Elem_Abstract
     * @throws Gb_Exception
     */
    final public function toStringRendersAs($type=null)
    {
        if ($type===null) { return $this->_toStringRendersAs; }
        $type=strtoupper($type);
        if ($type=="HTML") {$this->_toStringRendersAs="HTML";}
        elseif ($type=="JS") {$this->_toStringRendersAs="JS";}
        else { throw new Gb_Exception("type $type unhandled"); }
        return $this;
    }
    
    
}

/*
error_reporting(E_ALL | E_WARNING);
echo "<pre>";
$ab="ab";
$group=new Gb_Form_Group();
$group->dd="";
$group->$ab="abval";
$group
->__set("cd", "cdval")
->__set("ef", new Gb_Form_Elem_Text("efnom", array("efargs")))
->__set("gh", new Gb_Form_Elem_Password("ghnom", array("gharg1", "gharg2")))
;

$group2=new Gb_Form_Group();
$group2
->__set("ab2", "ab2val")
->__set("ef2", new Gb_Form_Elem_Hidden("ef2nom", array("ef2args")))
;

$group3=new Gb_Form_Group();
$group3
->__set("xx3", "xx3val")
;
$group2->append(new Gb_Form_Elem_Hidden("xx3bnom", array("ef2args")), $group3);


$group->__set("ij", $group2);

echo "dump:\n";
foreach ($group as $key=>$val) {
    echo "$key: ".print_r($val,true)."\n";
}


echo "dump:\n";
$rec=new RecursiveIteratorIterator($group);
foreach ($rec as $key=>$val) {
    echo "$key: ".print_r($val,true)."\n";
}
*/