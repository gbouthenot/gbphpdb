<?php
/**
 * Gb_Form_Elem_Abstract
 *
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}

require_once(_GB_PATH."Exception.php");


abstract class Gb_Form_Elem_Abstract
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
        'DecimalNumber'   => '/^(-?(\d*[\.\,])?\d+$)/',
        'AlphaNumeric'    => '/^([\w\s]+)$/i',
        'PostalCodeFr'    => '/^([0-9]{5})$/',
        'Year'            => '/^(((19)|(20))[0-9]{2})$/', // aaaa 1900<=aaaa<=2099
        'Year19xx'        => '/^(19[0-9]{2})$/',          // aaaa 1900<=aaaa<=1999
        'Year20xx'        => '/^(20[0-9]{2})$/',          // aaaa 2000<=aaaa<=2099
        'DateFr'          => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/(((19)|(20))[0-9]{2})$/', // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   1900<=aaaa<=2099
        'DateFr20xx'      => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/($20[0-9]{2})/',          // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   2000<=aaaa<=2099
    );

    /**
     * Renvoie la révision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
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
    protected $_beforeInput="";
    protected $_afterInput="";
    protected $_label="";
    protected $_preInput="";
    protected $_postInput="";
    protected $_inInput="";
    protected $_publicName="";
    protected $_value;
    protected $_backendCol;
    protected $_fMandatory=false;
    protected $_fReadOnly=false;
    protected $_toBackendFunc;
    protected $_fromBackendFunc;
    protected $_validateFunc;
    protected $_preInputContainer;
    protected $_postInputContainer;
    protected $_preLabel;
    protected $_postLabel;
    protected $_classContainer;
    protected $_classInput;
    protected $_classLabel;
    protected $_classPre;
    protected $_classPost;
    protected $_classStatut;
    protected $_container="div";
    protected $_errorContainer;
    protected $_errorMsg;
    protected $_errorMsgCustom;
    protected $_errorMsgMissing="Non rempli";
    protected $_disabled=false;
    protected $_placeholder;
    protected $_title;

    protected $_minValue=array();
    protected $_maxValue=array();
    protected $_notValue=array();
    protected $_regexp;

    protected $_javascriptRendered=false;
    protected $_htmlRendered=false;

    protected function __construct($name, array $availableParams=array(), array $aParams=array())
    {
        $this->_name=$name;

        $availableParams=array_merge($availableParams, array(
            "toStringRendersAs", "validateFunc", "postInput", "errorMsg", "preInputContainer", "preLabel",
            "javascriptEnabled", "fMandatory",   "container", "inInput",  "postInputContainer", "postLabel",
            "classStatut",       "name",         "toBackendFunc",  "backendCol",    "errorContainer",
            "publicName",        "fromBackendFunc",   "preInput",  "value",    "errorMsgMissing",
            "errorMsgCustom",    "disabled",     "fReadOnly",
            "javascriptRendered","htmlRendered", "label", "placeholder", "title",
            "classContainer", "classInput", "classLabel", "classPre", "classPost",
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
    public final function renderHtml()
    {
        $ret="";
        if (!$this->HtmlRendered()) {
            $preInput=$this->_preInput;
            $postInput=$this->_postInput;
            $inInput=$this->_inInput;
            $value=$this->value();
            if ($this->disabled()) {$inInput.=" disabled='disabled' "; }
            if ($this->fReadOnly()) {$inInput.=" readonly='readonly' "; }

            if ("Gb_Form_Elem_Print" === get_class($this)) {
                //return $preInput.$value.$postInput;
                $this->HtmlRendered(true);
                return $value;
            }

            $inputjs=$this->javascriptEnabled()?$this->getInputJavascript():"";
            $htmlInput=$this->getInput($value, $inInput, $inputjs);

            $preInputContainer=$this->preInputContainer();
            $postInputContainer=$this->postInputContainer();
            $container1=$container2="";$container1;$container2; // l'editeur dit qu'ils ne sont pas utilisés !
            $container=$this->container();
            $errorContainer=$this->errorContainer();
            $errorMsg=$this->errorMsg();
            $elemid=$this->elemId();
            if (strlen($container)) {
                $classStatut=$this->classStatut();
                $classContainer=$this->classContainer();
                if (strlen($classStatut)) { $classStatut="class='$classStatut $classContainer'"; } else { $classStatut="class='OKNOK $classContainer'"; }
                $container1="<$container id='{$elemid}_div' $classStatut >";
                $container2="</$container>";
            }
            if (strlen($errorContainer)) {
                $errorMsg="<$errorContainer class='ERROR'>$errorMsg</$errorContainer>";
            }
            if (strlen($preInput.$postInput)) {
                $classPre=$this->classPre();
                $classPost=$this->classPost();
                if (strlen($preInput)) {
                    $preInput  = ("<span class='PRE $classPre'>$preInput</span>");
                }
                if (strlen($postInput)) {
                    $postInput = ("<span class='POST $classPost'>$postInput</span>");
                }
            }
            $this->HtmlRendered(true);
            $ret=$preInputContainer.$container1.$preInput.$htmlInput.$postInput.$errorMsg.$container2.$postInputContainer."\n";
        }
        return $ret;
    }

    /**
     * Renders HTML label
     * @return string
     */
    public final function renderLabelHtml()
    {
        $text = $this->_label;
        $elemid = $this->elemId();
        $fLabel=true;
        $classLabel = $this->classLabel();
        $ret = "";
        if (get_class($this) == "Gb_Form_Elem_Radio") {
            $fLabel=false;
        }
        if (strlen($text)) {
            $ret = (($fLabel)?("<label for='$elemid' class='$classLabel'>"):("")) . ("<span class='LABEL'>$text</span>") . (($fLabel)?("</label>"):(""));
        }

        return $this->preLabel().$ret.$this->postLabel();
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
    public final function renderJavascript()
    {
        $ret="";
        if ($this->javascriptEnabled() && !$this->javascriptRendered()) {
            $this->javascriptRendered(true);
            $ret=$this->_renderjavascript();
        }
        return $ret;
    }

    protected function _renderjavascript($js=null)
    {
        $ret = "";
        if (!$this->fReadOnly() && strlen($js)) {
            $elemid  = $this->elemId();
            $ret     = "validate_$elemid=function() {\n";
            $ret    .= "    $js\n";
            $ret    .= "}\n";
        }
        return $ret;
    }

//    abstract public function isValid();

//    abstract public function validate();

    public function rawValue($text=null)
    {
        return $this->value($text);
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
     * get/set inInput
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function inInput($text=null, $mode="append")
    {
        if ($text===null) {           return $this->_inInput; }
        else { $this->_inInput=self::textSetter($text, $this->_inInput, $mode); return $this;}
    }
    /**
     * get/set preInput
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function preInput($text=null, $mode="append")
    {
        if ($text===null) {             return $this->_preInput; }
        else { $this->_preInput=self::textSetter($text, $this->_preInput, $mode); return $this;}
    }
    /**
     * get/set postInput
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function postInput($text=null, $mode="append")
    {
        if ($text===null) {             return $this->_postInput; }
        else { $this->_postInput=self::textSetter($text, $this->_postInput, $mode); return $this;}
    }
    /**
     * get/set beforeInput
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function beforeInput($text=null, $mode="append")
    {
        if ($text===null) {             return $this->_beforeInput; }
        else { $this->_beforeInput=self::textSetter($text, $this->_beforeInput, $mode); return $this;}
    }
    /**
     * get/set afterInput
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function afterInput($text=null, $mode="append")
    {
        if ($text===null) {             return $this->_afterInput; }
        else { $this->_afterInput=self::textSetter($text, $this->_afterInput, $mode); return $this;}
    }
    /**
     * get/set preInputContainer
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    public function preInputContainer($text=null, $mode="append")
    {
        if ($text===null) {         return $this->_preInputContainer; }
        else { $this->_preInputContainer=self::textSetter($text, $this->_preInputContainer, $mode); return $this;}
    }
    /**
     * get/set postInputContainer
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    public function postInputContainer($text=null, $mode="append")
    {
        if ($text===null) {         return $this->_postInputContainer; }
        else { $this->_postInputContainer=self::textSetter($text, $this->_postInputContainer, $mode); return $this;}
    }
    /**
     * get/set preLabel
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    public function preLabel($text=null, $mode="append")
    {
        if ($text===null) {         return $this->_preLabel; }
        else { $this->_preLabel=self::textSetter($text, $this->_preLabel, $mode); return $this;}
    }
    /**
     * get/set postLabel
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    public function postLabel($text=null, $mode="append")
    {
        if ($text===null) {         return $this->_postLabel; }
        else { $this->_postLabel=self::textSetter($text, $this->_postLabel, $mode); return $this;}
    }
    /**
     * get/set label
     * @param string[optional] $text
     * @param string[optional] "append" (default)/"prepend"/"set"
     * @return Gb_Form_Elem_Abstract|String
     */
    public function label($text=null, $mode="append")
    {
        if ($text===null) {         return $this->_label; }
        else { $this->_label=self::textSetter($text, $this->_label, $mode); return $this;}
    }
    /**
     * get/set value
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function value($text=null)
    {
        if ($text===null) {         return $this->_value; }
        else { if ($text===false) {$text="";} $this->_value=$text; return $this;}
    }
    /**
     * get/set backendValue
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
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
            } elseif (is_callable($func)) {
                $val=call_user_func($func, $val);
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
            } elseif (is_callable($func)) {
                $text=call_user_func($func, $text);
            }
            $this->value($text);
            return $this;
        }
    }
    /**
     * get/set backendCol si pas un string, unset
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function backendCol($text=null)
    {
        if ($text===null) {         return $this->_backendCol; }
        else { if (!is_string($text)) {$text=null;} $this->_backendCol=$text; return $this;}
    }
    /**
     * get/set fMandatory
     * @param boolean[optional] $flag
     * @return Gb_Form_Elem_Abstract|Boolean
     */
    final public function fMandatory($flag=null)
    {
        if ($flag===null) { return $this->_fMandatory; }
        if ($flag===false || $flag===true) { $this->_fMandatory=$flag; return $this;}
        else { throw new Gb_Exception("flag $flag not valid"); }
    }
    /**
     * get/set fReadOnly
     * @param boolean[optional] $text
     * @return Gb_Form_Elem_Abstract|Boolean
     */
    final public function fReadOnly($flag=null)
    {
        if ($flag===null) { return $this->_fReadOnly; }
        if ($flag===false || $flag===true) { $this->_fReadOnly=$flag; return $this;}
        else { throw new Gb_Exception("flag $flag not valid"); }
            }
    /**
     * get/set javascriptRendered
     * @param boolean[optional] $text
     * @return Gb_Form_Elem_Abstract|Boolean
     */
    final public function javascriptRendered($flag=null)
    {
        if ($flag===null) { return $this->_javascriptRendered; }
        if ($flag===false || $flag===true) { $this->_javascriptRendered=$flag; return $this;}
        else { throw new Gb_Exception("flag $flag not valid"); }
    }
    /**
     * get/set htmlRendered
     * @param boolean[optional] $text
     * @return Gb_Form_Elem_Abstract|Boolean
     */
    final public function htmlRendered($flag=null)
    {
        if ($flag===null) { return $this->_htmlRendered; }
        if ($flag===false || $flag===true) { $this->_htmlRendered=$flag; return $this;}
        else { throw new Gb_Exception("flag $flag not valid"); }
            }
    /**
     * get/set disabled
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function disabled($text=null)
    {
        if ($text===null) {         return $this->_disabled; }
        else { if ($text===true) {$this->javascriptEnabled(false);} $this->_disabled=$text; return $this;}
    }
    /**
     * get/set toBackendFunc
     * @param callable|string[optional] $callback
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function toBackendFunc($callback=null)
    {
        if ($callback===null) {         return $this->_toBackendFunc; return $this;}
        else { $this->_toBackendFunc=$callback; return $this;}
    }
    /**
     * get/set fromBackendFunc
     * @param callable|string[optional] $callback
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function fromBackendFunc($callback=null)
    {
        if ($callback===null) {         return $this->_fromBackendFunc; return $this;}
        else { $this->_fromBackendFunc=$callback; return $this;}
    }
    /**
     * get/set validateFunc
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function validateFunc($text=null)
    {
        if ($text===null) {         return $this->_validateFunc; return $this;}
        else { $this->_validateFunc=$text; return $this;}
    }
    /**
     * get/set errorMsgCustom
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function errorMsgCustom($text=null)
    {
        if ($text===null) {         return $this->_errorMsgCustom; return $this;}
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
     * @param Boolean $flag
     * @return Gb_Form_Elem_Abstract
     * @throws Gb_Exception
     */
    final public function javascriptEnabled($flag=null)
    {
        if ($flag===null) { return $this->_javascriptEnabled; }
        if ($flag===false || $flag===true) { $this->_javascriptEnabled=$flag; return $this;}
        else { throw new Gb_Exception("flag $flag not valid"); }
    }
    /**
     * get/set publicName
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function publicName($text=null)
    {
        if ($text===null) {         $publicName=$this->_publicName; if (strlen($publicName)==0) {$publicName=$this->name();} return $publicName; }
        else { $this->_publicName=$text; return $this;}
    }
    /**
     * get/set container
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function container($text=null)
    {
        if ($text===null) {         return $this->_container; }
        else { $this->_container=$text; return $this;}
    }
    /**
     * get/set errorContainer
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function errorContainer($text=null)
    {
        if ($text===null) {
            if ($this->_errorContainer!==null) {
                return $this->_errorContainer;
            } else {
                return $this->_container;
            }
        } else { $this->_errorContainer=$text; return $this;}
    }
    /**
     * get/set classContainer: the html class used by the <div> containing the preInput / input / postInput
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function classContainer($text=null)
    {
        if ($text===null) {         return $this->_classContainer; }
        else { $this->_classContainer=$text; return $this;}
    }
    /**
     * get/set classInput: the html class used inside the <input> / <select> / ...
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function classInput($text=null)
    {
        if ($text===null) {         return $this->_classInput; }
        else { $this->_classInput=$text; return $this;}
    }
    /**
     * get/set classLabel: the html class used by the <label>
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function classLabel($text=null)
    {
        if ($text===null) {         return $this->_classLabel; }
        else { $this->_classLabel=$text; return $this;}
    }
    /**
     * get/set classPre: the html class used in the <span class="PRE">. Not including "PRE".
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function classPre($text=null)
    {
        if ($text===null) {         return $this->_classPre; }
        else { $this->_classPre=$text; return $this;}
    }
    /**
     * get/set classPost: : the html class used in the <span class="POST">. Not including "POST".
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function classPost($text=null)
    {
        if ($text===null) {         return $this->_classPost; }
        else { $this->_classPost=$text; return $this;}
    }
    /**
     * get/set classStatut
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function classStatut($text=null)
    {
        if ($text===null) {         return $this->_classStatut; }
        else { $this->_classStatut=$text; return $this;}
    }
    /**
     * get/set placeholder: the 'placeholder' attribute inside the <input> tag
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function placeholder($text=null)
    {
        if ($text===null) {         return $this->_placeholder; }
        else { $this->_placeholder=$text; return $this;}
    }
    /**
     * get/set title: the 'title' attribute inside the <input> tag
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    public function title($text=null)
    {
        if ($text===null) {         return $this->_title; }
        else { $this->_title=$text; return $this;}
    }
    /**
     * get/set minValue
     * @param string|array[optional] $text
     * @return Gb_Form_Elem_Abstract|array
     */
    final public function minValue($text=null)
    {
        if ($text===null) {         return $this->_minValue; }
        else { if (!is_array($text)){$text=array($text);} $this->_minValue=$text; return $this;}
    }
    /**
     * get/set maxValue
     * @param string|array[optional] $text
     * @return Gb_Form_Elem_Abstract|array
     */
    final public function maxValue($text=null)
    {
        if ($text===null) {         return $this->_maxValue; }
        else { if (!is_array($text)){$text=array($text);} $this->_maxValue=$text; return $this;}
    }
    /**
     * get/set notValue
     * @param string|array[optional] $text
     * @return Gb_Form_Elem_Abstract|array
     */
    final public function notValue($text=null)
    {
        if ($text===null) {         return $this->_notValue; }
        else { if (!is_array($text)){$text=array($text);} $this->_notValue=$text; return $this;}
    }
    /**
     * get/set regexp MUST start and end with '/' (no modifiers), or be a commonRegex
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function regexp($text=null)
    {
        if ($text===null) {         return $this->_regexp;
        } else {
            if ("null"===$text) {$text=null;} elseif (isset(self::$_commonRegex[$text])){$text=self::$_commonRegex[$text];}
            $this->_regexp=$text; return $this;
        }
    }
    /**
     * Return regexp without the starting and ending /.
     * Used in HTML5 'pattern' attribute
     * Note that HTML5 pattern does not support modifiers
     * @return string
     */
    protected function _getStrippedRegexp()
    {
        $r = $this->regexp();
        if (substr($r, 0, 1) === '/' && substr($r, -1, 1) === '/') {
            return substr($r, 1, -1);
        }
        return $r;
    }
    /**
     * get/set errorMsg and sets classStatut
     * @param false|string[optional] $text si false ou string, dévalide l'élément et écrit l'erreur
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function errorMsg($text=null)
    {
        if ($text===null) {         return $this->_errorMsg; }
        else { $this->_errorMsg=$text; if (strlen($text)==0 && $text!==false){$class="OK";}else{$class="NOK";}$this->classStatut($class);return $this;}
    }
    /**
     * get/set errorMsgMissing
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function errorMsgMissing($text=null)
    {
        if ($text===null) {         return $this->_errorMsgMissing; }
        else { $this->_errorMsgMissing=$text; return $this;}
    }
    /**
     * get/set name
     * @param string[optional] $text
     * @return Gb_Form_Elem_Abstract|String
     */
    final public function name($text=null)
    {
        if ($text===null) {         return $this->_name; }
        else { $this->_name=$text; return $this;}
    }

}
