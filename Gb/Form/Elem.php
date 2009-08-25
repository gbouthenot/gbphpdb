<?php

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

/**
 * Gb_Form_Elem
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

abstract class Gb_Form_Elem
{
    protected static $_commonRegex = array(
        'Name'            => '/^[ a-z\-\']{2,25}$/',
        'FirstName'       => '/^[ a-z\-\']{3,25}$/',
        'HexColor'        => '/^(#?([\dA-F]{3}){1,2})$/i',
        'UsTelephone'     => '/^(\(?([2-9]\d{2})\)?[\.\s-]?([2-4|6-9]\d\d|5([0-4|6-9]\d|\d[0-4|6-9]))[\.\s-]?(\d{4}))$/',
        'Email'           => '/((^[\w\.!#$%"*+\/=?`{}|~^-]+)@(([-\w]+\.)+[A-Za-z]{2,}))$/',
        'Url'             => '/^((https?|ftp):\/\/([-\w]+\.)+[A-Za-z]{2,}(:\d+)?([\\\\\/]\S+)*?[\\\\\/]?(\?\S*)?)$/i',
        'PositiveInteger' => '/^(\d+)$/',
        'RelativeInteger' => '/^(-?\d+)$/',
        'DecimalNumber'   => '/^(-?(\d*\.)?\d+$)/',
        'AlphaNumeric'    => '/^([\w\s]+)$/i',
        'PostalCodeFr'    => '/^([0-9]{5})$/',
        'Year'            => '/^(((19)|(20))[0-9]{2})$/', // aaaa 1900<=aaaa<=2099
        'Year19xx'        => '/^(19[0-9]{2})$/',          // aaaa 1900<=aaaa<=1999
        'Year20xx'        => '/^(20[0-9]{2})$/',          // aaaa 2000<=aaaa<=2099
        'DateFr'          => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/(((19)|(20))[0-9]{2})$/', // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   1900<=aaaa<=2099
        'DateFr20xx'      => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/($20[0-9]{2})/',          // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   2000<=aaaa<=2099
    );
    
    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision$';
        $revision=trim(substr($revision, strrpos($revision, ":")+2, -1));
        if ($mini===null) { return $revision; }
        if ($revision>=$mini) { return true; }
        if ($throw) { throw new Gb_Exception(__CLASS__." r".$revision."<r".$mini); }
        return false;
    }
    
    protected $_name;
    
    protected $_toStringRendersAs="HTML";
    protected $_javascriptEnabled=true;
    protected $_preInput="";
    protected $_postInput="";
    protected $_inInput="";
    protected $_publicName="";
    protected $_value;
    protected $_backendCol;
    protected $_fMandatory=false;
    protected $_toBackendFunc;
    protected $_fromBackendFunc;
    protected $_validateFunc;
    protected $_preElem;
    protected $_postElem;
    protected $_classStatut;
    protected $_container="div";
    protected $_errorContainer;
    protected $_errorMsg;
    protected $_errorMsgCustom;
    protected $_errorMsgMissing="Non rempli";
    protected $_disabled=false;
    
    protected $_minValue=array();
    protected $_maxValue=array();
    protected $_notValue=array();
    protected $_regexp;
    
    protected function __construct($name, array $availableParams=array(), array $aParams=array())
    {
        $this->_name=$name;

        $availableParams=array_merge($availableParams, array(
            "toStringRendersAs", "validateFunc", "postInput", "errorMsg", "preElem",
            "javascriptEnabled", "fMandatory",   "container", "inInput",  "postElem",
            "classStatut",       "name",         "toBackendFunc",  "backendCol",    "errorContainer",
            "publicName",        "fromBackendFunc",   "preInput",  "value",    "errorMsgMissing",
            "errorMsgCustom",    "disabled",
        ));
        
        foreach ($availableParams as $key) {
            if (isset($aParams[$key])) {
                $val=$aParams[$key];
                call_user_func(array($this, $key), $val);
            }
        }
        
        if (!isset($aParams["backendCol"])) {
            $this->backendCol($name);
        }
    }

    
    /**
     * Renders HTML or Javascript, depending on $this->toStringRendersAs()
     *
     * @return string
     */
    final public function __toString()
    {
        return $this->render();
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
     * Renders HTML
     * @return string
     */
    public function renderHtml()
    {
        $preInput=$this->_preInput;
        $postInput=$this->_postInput;
        $inInput=$this->_inInput;
        $value=$this->value();
        if ($this->disabled()) {$inInput.=" disabled='disabled' "; }
        
        $inputjs=$this->javascriptEnabled()?$this->getInputJavascript():"";
        $htmlInput=$this->getInput($value, $inInput, $inputjs);
        
        $preElem=$this->preElem();
        $postElem=$this->postElem();
        $container1=$container2="";$container1;$container2; // l'editeur dit qu'ils ne sont pas utilisés !
        $container=$this->container();
        $errorContainer=$this->errorContainer();
        $errorMsg=$this->errorMsg();
        $elemid=$this->elemId();
        if (strlen($container)) {
            $classStatut=$this->classStatut();
            if (strlen($classStatut)) { $classStatut="class='$classStatut'"; } else { $classStatut="class='OKNOK'"; }
            $container1="<$container id='{$elemid}_div' $classStatut >";
            $container2="</$container>";
        }
        if (strlen($errorContainer)) {
            $errorMsg="<$errorContainer class='ERROR'>$errorMsg</$errorContainer>";
        }
        if (strlen($preInput.$postInput)) {
            $preInput="<label>".(strlen($preInput)?"<span class='PRE'>$preInput</span>":"");
            $postInput=(strlen($postInput)?"<span class='POST'>$postInput</span>":"")."</label>";
        }
        return $preElem.$container1.$preInput.$htmlInput.$postInput.$errorMsg.$container2.$postElem."\n";
    }

    public function getAjaxArgs()
    {
        $gbname=$this->elemId();
        
        return "{$gbname}: \$F('{$gbname}')";
    }
    
    /**
     * Returns the elem id (GBFORM_name)
     *
     * @return string
     */
    public function elemId()
    {
        return "GBFORM_".$this->name();
    }
    
    protected function getInput($value, $inInput, $inputjs)
    {
        $value;
        $inInput;
        $inputjs;
        return "";
    }
    protected function getInputJavascript()
    {
        return "";
    }
    public function validate(Gb_Form2 $form)
    {
        $form;
        return null;
    }    

        
    /**
     * Renders Javascript
     * @return string
     */
    public function renderJavascript()
    {
        return "";
    }
    
//    abstract public function isValid();
    
//    abstract public function validate();

    public function rawValue($text=null)
    {
        return $this->value($text);
    }
        
    /**
     * get/set inInput
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function inInput($text=null)
    {   
        if ($text===null) {           return $this->_inInput; }
        else { $this->_inInput=$text; return $this;}
    }
    /**
     * get/set preInput
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function preInput($text=null)
    {   
        if ($text===null) {             return $this->_preInput; }
        else { $this->_preInput=$text; return $this;}
    }
    /**
     * get/set postInput
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function postInput($text=null)
    {   
        if ($text===null) {             return $this->_postInput; }
        else { $this->_postInput=$text; return $this;}
    }
    /**
     * get/set value
     * @param string[optional] $text
     * @return Gb_Form_Elem|String 
     */
    public function value($text=null)
    {   
        if ($text===null) {         return $this->_value; }
        else { if ($text===false) {$text="";} $this->_value=$text; return $this;}
    }
    /**
     * get/set backendValue
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function backendValue($text=null)
    {   
        if ($text===null) {
            $func=$this->toBackendFunc();
            $val=$this->value();
            if (is_array($func)) {
                $params=$func[1]; if (!is_array($params)) {$params=array($params);}
                $func=$func[0];
                foreach ($params as &$param) {
                    if (is_string($param)) {
                        $param=sprintf($param, $val);
                    }
                }
                $val=call_user_func_array($func, $params);
            }
            return $val;
        } else {
            $func=$this->fromBackendFunc();
            if (is_array($func)) {
                $params=$func[1]; if (!is_array($params)) {$params=array($params);}
                $func=$func[0];    
                foreach ($params as &$param) {
                    if (is_string($param)) {
                        $param=sprintf($param, $text);
                    }
                }
                $text=call_user_func_array($func, $params);
            }
            $this->value($text);
            return $this;
        }
    }
    /**
     * get/set backendCol si pas un string, unset
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function backendCol($text=null)
    {   
        if ($text===null) {         return $this->_backendCol; }
        else { if (!is_string($text)) {$text=null;} $this->_backendCol=$text; return $this;}
    }
    /**
     * get/set fMandatory
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function fMandatory($text=null)
    {   
        if ($text===null) {         return $this->_fMandatory; }
        else { $this->_fMandatory=$text; return $this;}
    }
    /**
     * get/set disabled
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function disabled($text=null)
    {   
        if ($text===null) {         return $this->_disabled; }
        else { if ($text===true) {$this->javascriptEnabled(false);} $this->_disabled=$text; return $this;}
    }
    /**
     * get/set toBackendFunc
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function toBackendFunc(array $text=null)
    {   
        if ($text===null) {         return $this->_toBackendFunc; }
        else { $this->_toBackendFunc=$text; return $this;}
    }
    /**
     * get/set fromBackendFunc
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function fromBackendFunc(array $text=null)
    {   
        if ($text===null) {         return $this->_fromBackendFunc; }
        else { $this->_fromBackendFunc=$text; return $this;}
    }
    /**
     * get/set validateFunc
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function validateFunc($text=null)
    {   
        if ($text===null) {         return $this->_validateFunc; }
        else { $this->_validateFunc=$text; return $this;}
    }
    /**
     * get/set errorMsgCustom
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function errorMsgCustom($text=null)
    {   
        if ($text===null) {         return $this->_errorMsgCustom; }
        else { $this->_errorMsgCustom=$text; return $this;}
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
    /**
     * Enable / Disable javascript
     *
     * @param boolean $flag
     * @return Gb_Form_Elem_Abstract
     * @throws Gb_Exception
     */
    final public function javascriptEnabled($flag=null)
    {
        if ($flag===null) { return $this->_javascriptEnabled; }
        if ($flag===false || $flag===true) { $this->_javascriptEnabled=$flag; }
        else { throw new Gb_Exception("flag $flag not valid"); }
        return $this;
    }
    /**
     * get/set publicName
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    public function publicName($text=null)
    {   
        if ($text===null) {         $publicName=$this->_publicName; if (strlen($publicName)==0) {$publicName=$this->name();} return $publicName; }
        else { $this->_publicName=$text; return $this;}
    }
    /**
     * get/set preElem
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    public function preElem($text=null)
    {   
        if ($text===null) {         return $this->_preElem; }
        else { $this->_preElem=$text; return $this;}
    }
    /**
     * get/set container
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    public function container($text=null)
    {   
        if ($text===null) {         return $this->_container; }
        else { $this->_container=$text; return $this;}
    }
    /**
     * get/set errorContainer
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    public function errorContainer($text=null)
    {   
        if ($text===null) { if ($this->_errorContainer!==null) 
        { return $this->_errorContainer; }
         else 
         { return $this->_container; } }
        else { $this->_errorContainer=$text; return $this;}
    }
    /**
     * get/set postElem
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    public function postElem($text=null)
    {   
        if ($text===null) {         return $this->_postElem; }
        else { $this->_postElem=$text; return $this;}
    }
    /**
     * get/set classStatut
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    public function classStatut($text=null)
    {   
        if ($text===null) {         return $this->_classStatut; }
        else { $this->_classStatut=$text; return $this;}
    }
    /**
     * get/set minValue
     * @param string|array[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|array 
     */
    final public function minValue($text=null)
    {   
        if ($text===null) {         return $this->_minValue; }
        else { if (!is_array($text)){$text=array($text);} $this->_minValue=$text; return $this;}
    }
    /**
     * get/set maxValue
     * @param string|array[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|array 
     */
    final public function maxValue($text=null)
    {   
        if ($text===null) {         return $this->_maxValue; }
        else { if (!is_array($text)){$text=array($text);} $this->_maxValue=$text; return $this;}
    }
    /**
     * get/set notValue
     * @param string|array[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|array
     */
    final public function notValue($text=null)
    {   
        if ($text===null) {         return $this->_notValue; }
        else { if (!is_array($text)){$text=array($text);} $this->_notValue=$text; return $this;}
    }
    /**
     * get/set regexp
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function regexp($text=null)
    {   
        if ($text===null) {         return $this->_regexp; }
        else { if (isset(self::$_commonRegex[$text])){$text=self::$_commonRegex[$text];} $this->_regexp=$text; return $this;}
    }
    /**
     * get/set errorMsg and sets classStatut
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function errorMsg($text=null)
    {   
        if ($text===null) {         return $this->_errorMsg; }
        else { $this->_errorMsg=$text; if (strlen($text)==0){$class="OK";}else{$class="NOK";}$this->classStatut($class);return $this;}
    }
    /**
     * get/set errorMsgMissing
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function errorMsgMissing($text=null)
    {   
        if ($text===null) {         return $this->_errorMsgMissing; }
        else { $this->_errorMsgMissing=$text; return $this;}
    }
    /**
     * get/set name
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    final public function name($text=null)
    {   
        if ($text===null) {         return $this->_name; }
        else { $this->_name=$text; return $this;}
    }
    
}
