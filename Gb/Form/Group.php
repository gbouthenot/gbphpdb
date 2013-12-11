<?php
/**
 * Gb_Form_Group
 *
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Form/Iterator.php");
require_once(_GB_PATH."Form/Elem/Abstract.php");


class Gb_Form_Group implements IteratorAggregate
{
    protected $_elems;
    protected $_name;
    protected $_modifiers;

    protected $_toStringRendersAs="HTML";

    protected $_preGroup;
    protected $_postGroup;

    protected $_fGrouped=false;
    protected $_format = "_LABELS__ELEMS_";
    protected $_labelFormat = "_LABEL_";
    protected $_elemFormat = "_ELEM_";

    /**
     * Constructeur de Gb_Form_Group
     *
     * @param array[optional] $aParams
     * @param array[optional] $modifiers : arguments qui modifiront les paramètres des enfants de ce groupe
     */
    public function __construct($name, $aParams=array(), $modifiers=array())
    {
       $this->_name=$name;
       $this->_elems=array();
       $this->_modifiers=$modifiers;

       $availableParams=array(
            "toStringRendersAs", "preGroup", "postGroup", "fGrouped", "format", "labelFormat", "elemFormat",
        );

        foreach ($availableParams as $key) {
            if (isset($aParams[$key])) {
                $val=$aParams[$key];
                call_user_func(array($this, $key), $val);

            }
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
                    if (is_string($str)) {
                        $arg=call_user_func(array($obj, $key));
                        $arg=sprintf($str, $arg);
                    } else {
                        $arg=$str;
                    }
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

    /**
     * Ajoute des Gb_Form_Element au groupe
     *
     * @return Gb_Form_Group
     */
    final public function append()
    {
        $args=func_get_args();
        foreach ($args as $arg) {
            $arg = $this->_applyModifiers($arg);
            $name = $arg->name();
            if (null !== $name) {
                $this->$name = $arg;
            } else {
                $this->_elems[] = $arg;
            }
        }
        return $this;
    }

    /**
     * search a elem by name recursively. To get en elem without recursion, use object->elemName OOP function
     *
     * @param string $name
     * @param boolean $fDontThrow
     * @return Gb_Form_Elem_Abstract|false
     * @throws Gb_Exception
     */
    public function getElem($name, $fDontThrow=null)
    {
        foreach (new RecursiveIteratorIterator($this->getIterator()) as $elem) {
            $c=get_class($elem);
            if (substr($c, 0, 13)=="Gb_Form_Elem_") {
                if ($elem->name()==$name) {
                    return $elem;
                }
            }
        }
        if ($fDontThrow) {
            return false;
        }
        throw new Gb_Exception("Element $name not found");
    }


    final public function getKeys()
    {
        return array_keys($this->_elems);
    }

    final public function __toString()
    {
        return $this->render();
    }
    final public function renderHtml($aElemNames=null)
    {
        $ret = "";
        $format = $this->_format;
        $labelFormat = $this->_labelFormat;
        $elemFormat = $this->_elemFormat;

        if (is_string($aElemNames)) {
            $aElemNames=array($aElemNames);
        }

        if (false == $this->fGrouped()) {
            // not grouped
            foreach ($this as $elemName=>$elemOrGroup) {
                if ($elemOrGroup instanceof Gb_Form_Elem_Abstract || $elemOrGroup instanceOf Gb_Form_Group) {
                    if (($aElemNames===null) || in_array($elemName, $aElemNames)) {
                        $renderHtml = $elemOrGroup->renderHtml();
                        if (strlen($renderHtml)) {
                            if ($elemOrGroup instanceof Gb_Form_Elem_Abstract) {
                                $sLabel = str_replace("_LABEL_", $elemOrGroup->renderLabelHtml(), $labelFormat);
                                $sElem  = str_replace("_ELEM_",  $renderHtml                    , $elemFormat);

                                $sElem2 = $format;
                                $sElem2 = str_replace("_LABELS_", $sLabel, $sElem2);
                                $sElem2 = str_replace("_ELEMS_"  , $sElem,  $sElem2);

                                $ret .= $sElem2;
                            } else {
                                $ret .= $renderHtml;
                            }
                        }
                    }
                }
            }
        } else {
            // grouped

            $aLabels = array();
            $aElems = array();

            foreach ($this as $elemName=>$elemOrGroup) {
                if ($elemOrGroup instanceof Gb_Form_Elem_Abstract || $elemOrGroup instanceOf Gb_Form_Group) {
                    if (($aElemNames===null) || in_array($elemName, $aElemNames)) {
                        $renderHtml = $elemOrGroup->renderHtml();
                        if (strlen($renderHtml)) {
                            if ($elemOrGroup instanceof Gb_Form_Elem_Abstract) {
                                $aLabels[] = str_replace("_LABEL_", $elemOrGroup->renderLabelHtml(), $labelFormat);
                                $aElems[]  = str_replace("_ELEM_",  $renderHtml,                     $elemFormat);
                            } else {
                                $ret .= $renderHtml;
                            }
                        }
                    }
                }
            }

            if (count($aElems)) {
                $format = str_replace("_LABELS_", join("", $aLabels), $format);
                $format = str_replace("_ELEMS_", join("", $aElems), $format);

                $ret .= $format;
            }

        }

        if (strlen($ret)) {
            $ret  = $this->preGroup().$ret;
            $ret .= $this->postGroup();
            $ret .= "\n";
        }

        return $ret;
    }
    final public function renderJavascript($aElemNames=null)
    {
        $ret="";

        if ($aElemNames===null) {
            foreach ($this as $elemOrGroup) {
                if ($elemOrGroup instanceof Gb_Form_Elem_Abstract || $elemOrGroup instanceOf Gb_Form_Group) {
                    $ret.=$elemOrGroup->renderJavascript();
                }
            }
        } else {
            if (is_string($aElemNames)) {
                $aElemNames=array($aElemNames);
            }
            foreach ($aElemNames as $elemname) {
                $elemOrGroup=$this->getElem($elemname);
                if ($elemOrGroup instanceof Gb_Form_Elem_Abstract || $elemOrGroup instanceOf Gb_Form_Group) {
                    $ret.=$elemOrGroup->renderJavascript();
                }
            }
        }

        return $ret;
    }
    final public function render($aElemNames=null)
    {   $ret="";
        if ($this->_toStringRendersAs=="HTML") {
            $ret=$this->renderHtml($aElemNames);
        } elseif ($this->_toStringRendersAs=="JS") {
            $ret=$this->renderJavascript($aElemNames);
        } elseif ($this->_toStringRendersAs=="BOTH") {
            $ret=$this->renderHtml($aElemNames);
            $ret=$this->renderJavascript($aElemNames, true);
        }
        return $ret;
    }
    final public function getAjaxArgs()
    {
        $ret="";
        foreach ($this as $elem) {
            if ($elem instanceof Gb_Form_Elem_Abstract || $elem instanceof Gb_Form_Group) {
                $arg=$elem->getAjaxArgs();
                if (strlen($arg)) {
                    $ret.=(strlen($ret)?",":"").$arg;
                }
            }
        }
        return $ret;
    }

    protected static function textSetter($newtext, $oldtext=null, $mode="append")
    {
        switch($mode) {
            case "append": return $oldtext.$newtext;
            case "prepend": return $newtext.$oldtext;
            case "set": return $newtext;
            default: throw new Gb_Exception("mode $mode unhandled");
        }
    }


    /**
     * get/set preGroup
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Group|String
     */
    final public function preGroup($text=null, $mode="append")
    {
        if ($text===null) {         return $this->_preGroup; }
        else { $this->_preGroup=self::textSetter($text, $this->_preGroup, $mode); return $this;}
    }
    /**
     * get/set postGroup
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Group|String
     */
    final public function postGroup($text=null, $mode="append")
    {
        if ($text===null) {         return $this->_postGroup; }
        else { $this->_postGroup=self::textSetter($text, $this->_postGroup, $mode); return $this;}
    }
    /**
     * get/set fGrouped
     * @param boolean[optional] $text
     * @return Gb_Form_Group|String
     */
    final public function fGrouped($text=null)
    {
        if ($text===null) {         return $this->_fGrouped; }
        else { $this->_fGrouped=$text; return $this;}
    }
    /**
     * get/set format
     * @param string[optional] $text
     * @return Gb_Form_Group|String
     */
    final public function format($text=null)
    {
        if ($text===null) {         return $this->_format; }
        else { $this->_format=$text; return $this;}
    }
    /**
     * get/set labelFormat
     * @param string[optional] $text
     * @return Gb_Form_Group|String
     */
    final public function labelFormat($text=null)
    {
        if ($text===null) {         return $this->_labelFormat; }
        else { $this->_labelFormat=$text; return $this;}
    }
    /**
     * get/set elemFormat
     * @param string[optional] $text
     * @return Gb_Form_Group|String
     */
    final public function elemFormat($text=null)
    {
        if ($text===null) {         return $this->_elemFormat; }
        else { $this->_elemFormat=$text; return $this;}
    }
    /**
     * Set the type of data returned by __toString()
     *
     * @param string $type "HTML" or "JS"
     * @return Gb_Form_Group
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
    /**
     * get/set name
     * @param string[optional] $text
     * @return Gb_Form_Group|String
     */
    final public function name($text=null)
    {
        if ($text===null) {         return $this->_name; }
        else { $this->_name=$text; return $this;}
    }


}

/*
error_reporting(E_ALL | E_WARNING);
echo "<pre>";
$ab="ab";
$group=new Gb_Form_Group("GRP1");
$group->dd="";
$group->$ab="abval";
$group
->__set("cd", "cdval")
->__set("ef", new Gb_Form_Elem_Text("efnom", array("efargs")))
->__set("gh", new Gb_Form_Elem_Password("ghnom", array("gharg1", "gharg2")))
;

$group2=new Gb_Form_Group("GRP2");
$group2
->__set("ab2", "ab2val")
->__set("ef2", new Gb_Form_Elem_Hidden("ef2nom", array("ef2args")))
;

$group3=new Gb_Form_Group("GRP3");
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
