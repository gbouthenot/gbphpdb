<?php

/**
 * Gb_Form2
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
require_once(_GB_PATH."Form/Iterator.php");


Class Gb_Form2 implements IteratorAggregate, ArrayAccess
{
    protected $_elems;
    protected $_modifiers;

    //
    // Properties -> these functions are handled by functions named after the properties name i.e. action() sets the _action
    //
    protected $_action;
    protected $_enctype="multipart/form-data";
    protected $_acceptCharset="utf-8";
    protected $_errors;
    protected $_formHash;
    protected $_hasData;
    protected $_isLoaded;
    protected $_isPost;
    protected $_isValid;
    protected $_method="post";
    protected $_classForm;
    protected $_moreDataRead=array();
    protected $_renderFormTags=true;
    protected $_toStringRendersAs="BOTH";

    protected $_formTagOpened=false;
    protected $_formPostTagIssued=false;
    protected $_formTagClosed=false;

    /**
     * @var Gb_Form_Backend_Abstract
     */
    protected $_backend=null;

    /**
     * Renvoie la révision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
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
     * constructeur
     *
     * @param array[optional] $aParams
     * @param array[optional] $modifiers : arguments qui modifiront les paramètres des enfants
     */
    public function __construct($aParams=array(), $modifiers=array())
    {
        $this->_elems=array();
        $this->_modifiers=$modifiers;

        $availableParams=array(
            "action", "enctype", "acceptCharset", "errors", "formHash", "hasData",
            "isLoaded", "isPost", "isValid", "backend",
            "method", "moreDataRead", "renderFormTags", "toStringRendersAs",
            "formTagOpened", "formPostTagIssued", "formTagClosed" , "classForm",
        );

        foreach ($availableParams as $key) {
            if (isset($aParams[$key])) {
                $val=$aParams[$key];
                call_user_func(array($this, $key), $val);
            }
        }

    }

// implements InteratorAggregate START
    final public function getIterator()
    {
        return new Gb_Form_Iterator($this);
    }
// implements InteratorAggregate END

// implements ArrayAccess START
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
// implements ArrayAccess END


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
     * Ajoute des Gb_Form_Element au formulaire
     *
     * @return Gb_Form2
     */
    final public function append()
    {
        $args=func_get_args();
        foreach ($args as $arg) {
            $arg = $this->_applyModifiers($arg);
            $name = $arg->name();
            if ($name !== null) {
                $this->$name = $arg;
            } else {
                $this->_elems[] = $arg;
            }
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

    /**
     * Renvoie la partie HTML du formulaire
     * @param array[optional] $aElemNames
     * @return string
     */
    final public function renderHtml($aElemNames=null)
    {
        $ret="";

        // the <FORM> tag
        if ($this->renderFormTags() && !$this->formTagOpened()) {
            $ret.=$this->_renderFormOpenTag()."\n";
            $this->formTagOpened(true);
        }

        // the <INPUT TYPE="HIDDEN"> used to identified if the form has POST data
        if (!$this->formPostTagIssued()) {
            $ret.=$this->renderFormPostTag()."\n";
            $this->formPostTagIssued(true);
        }

        if ($aElemNames===null) {
            foreach ($this as $elemOrGroup) {
                if ($elemOrGroup instanceof Gb_Form_Elem_Abstract || $elemOrGroup instanceOf Gb_Form_Group) {
                    $ret.=$elemOrGroup->renderHtml();
                }
            }
        } else {
            if (is_string($aElemNames)) {
                $aElemNames=array($aElemNames);
            }
            foreach ($aElemNames as $elemname) {
                $elemOrGroup=$this->getElem($elemname);
                if ($elemOrGroup instanceof Gb_Form_Elem_Abstract || $elemOrGroup instanceOf Gb_Form_Group) {
                    $ret.=$elemOrGroup->renderHtml();
                }
            }
        }

        // the </FORM> tag
        if ($this->renderFormTags() && $aElemNames===null && !$this->formTagClosed()) {
            // if the </form> should be rendered, renders it only if RenderHtml() was called without element name to render
            $ret.=$this->renderFormCloseTag()."\n";
            $this->formTagClosed(true);
        }
        return $ret;
    }

    // when publically called, assume that renderFormCloseTag() will be issued
    final public function renderFormOpenTag()
    {
        $this->renderFormTags(false);
        return $this->_renderFormOpenTag();
    }
    final protected function _renderFormOpenTag()
    {
        $ret="";
        if (!$this->formTagOpened()) {
            $method=$this->method();   if (strlen($method))  {$method="method='$method'";}
            $action=$this->action();   if (strlen($action))  {$action="action='$action'";}
            $enctype=$this->enctype(); if (strlen($enctype)) {$enctype="enctype='$enctype'";}
            $class=$this->classForm(); if (strlen($class))   {$class="class='$class'";}
            $acceptCharset=$this->acceptCharset(); if (strlen($acceptCharset)) {$acceptCharset="accept-charset='$acceptCharset'";}
            $ret="<form $method $action $enctype $acceptCharset $class>";
            $this->formTagOpened(true);
        }
        return $ret;
    }
    final public function renderFormPostTag()
    {
        $ret="";
        if (!$this->formPostTagIssued()) {
            $hash=$this->formHash();
            $ret="<input type='hidden' name='GBFORMPOST' value='$hash' />";
            $this->formPostTagIssued(true);
        }
        return $ret;
    }
    final public function renderFormCloseTag()
    {
        $ret="";
        if (!$this->formTagClosed()) {
            $ret="</form>";
            $this->formTagClosed(true);
        }
        return $ret;
    }
    final public function renderJavascript($aElemNames=null, $fRenderScriptTag=null)
    {
        if ( ($aElemNames!==null) || ($fRenderScriptTag===null) ) {
            $fRenderScriptTag=false;
        }
        $ret = <<<EOF
// see http://blog.stevenlevithan.com/archives/faster-trim-javascript
// for more trim functions
if (window.gbtrim == undefined)
window.gbtrim = function(str){return str.replace(/^\s\s*/, '').replace(/\s\s*\$/, '');}
if (window.remove_accents == undefined)
window.remove_accents = function(my_string){
  var new_string = "";
  var pattern_accent =         new Array("é", "è", "ê", "ë", "ç", "à", "â", "ä", "ì", "î", "ï", "ù", "ò", "ô", "ó", "ö");
  var pattern_replace_accent = new Array("e", "e", "e", "e", "c", "a", "a", "a", "i", "i", "i", "u", "o", "o", "o", "o");
  var preg_replace = function (array_pattern, array_pattern_replace, my_string) {
    var new_string = String (my_string);
    for (i=0; i<array_pattern.length; i++) {
      var reg_exp= RegExp(array_pattern[i], "gi");
      var val_to_replace = array_pattern_replace[i];
      new_string = new_string.replace (reg_exp, val_to_replace);
    }
    return new_string;
  }
  if (my_string && my_string!= "") {
      my_string=my_string.toLowerCase();
      new_string = preg_replace (pattern_accent, pattern_replace_accent, my_string);
      return new_string;
  }
};
if (window.gbSetClass == undefined)
window.gbSetClass = function(id, classname) {
  if (window.YUI != undefined) {
    YUI().use('node', function(Y) {
      Y.one('#' + id).setAttribute("class", classname);
    });
  } else if (window.jQuery != undefined) {
    jQuery('#' + id).attr("class", classname);
  } else {
    \$(id).className = classname;
  }
};
if (window.gbRemoveError == undefined )
window.gbRemoveError = function(id) {
  if (window.YUI != undefined) {
    YUI().use('node', function(Y) {
      var div = Y.one('#' + id + '_div span.ERROR');
      if (div != null) { div.setContent(); }
      var div = Y.one('#' + id + '_div div.ERROR');
      if (div != null) { div.setContent(); }
    });
  } else if (window.jQuery != undefined) {
    jQuery('#' + id + '_div span.ERROR').html();
    jQuery('#' + id + '_div div.ERROR').html();
  } else {
    var e=\$(id + '_div').select('div[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}
    var e=\$(id + '_div').select('span[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}
    }
};
if (window.\$F == undefined )
window.\$F = function(id){return document.getElementById(id).value;};
EOF;

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

        if ($fRenderScriptTag && strlen($ret)) {
            $head= "<script type='text/javascript'>\n";
            $head.="/* <![CDATA[ */\n";
            $ret=$head.$ret;
            $ret.="/* ]]> */\n";
            $ret.="</script>\n";
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
            $ret.=$this->renderJavascript($aElemNames, true);
        }
        return $ret;
    }
    final public function getAjaxArgs()
    {
        $hash=$this->formHash();
        $ret="GBFORMPOST: '$hash'";

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

    /**
     * get/set action
     * @param string[optional] $text
     * @return Gb_Form2|String
     */
    final public function action($text=null)
    {
        if ($text===null) {         return $this->_action; }
        else { $this->_action=$text; return $this;}
    }
    /**
     * get/set enctype
     * @param string[optional] $text
     * @return Gb_Form2|String
     */
    final public function enctype($text=null)
    {
        if ($text===null) {         return $this->_enctype; }
        else { $this->_enctype=$text; return $this;}
    }
    /**
     * get/set accept-charset form attribute
     * @param string[optional] $text
     * @return Gb_Form2|String
     */
    final public function acceptCharset($text=null)
    {
        if ($text===null) {         return $this->_acceptCharset; }
        else { $this->_acceptCharset=$text; return $this;}
    }
    /**
     * Get/set errors. Validate if hasData. Else returns null
     *
     * @param array[optional] $param
     * @return Gb_Form2|array
     */
    public function errors(array $param=null)
    {
        if ($param===null) {
            if ($this->_errors===null) {
                // Si le formulaire a des données, le valider
                if ($this->hasData()) {
                    $this->validate(false);
                }
            }
            return $this->_errors;
        } else {
            $this->_errors=$param;
            return $this;
        }
    }
    /**
     * Get/set formhash. Compute it
     *
     * @param string[optional] $hash
     * @return Gb_Form2|string
     */
    final public function formHash($hash=null)
    {
        if ($hash===null) {
            if ($this->_formHash===null) {
                // Trie les noms par ordre alphabétiques si les éléments n'ont pas été définis dans le même ordre
                $keys=array();
                foreach (new RecursiveIteratorIterator($this->getIterator()) as $name=>$elem) {
                    $c=get_class($elem);
                    if (substr($c, 0, 13)=="Gb_Form_Elem_") {
                        $name=$elem->name();
                    }
                    $keys[]=$name;
                }
                sort($keys, SORT_STRING);
                $this->_formHash=md5(serialize($keys));
            }
            return $this->_formHash;
        } else {
            $this->_formHash=$hash;
            return $this;
        }
    }
    /**
     * get/set hasData
     * @param boolean[optional] $text
     * @return Gb_Form2|boolean
     */
    final public function hasData($text=null)
    {
        if ($text===null) {         return $this->_hasData; }
        else { $this->_hasData=$text; return $this;}
    }
    /**
     * Get/set isLoaded
     *
     * @param boolean[optional] $param
     * @return Gb_Form2|boolean
     */
    final public function isLoaded($param=null)
    {
        if ($param===null) {         return $this->_isLoaded; }
        else { $this->_isLoaded=$param; return $this;}
    }
    /**
     * Get/set isPost Computes hash
     *
     * @param boolean[optional] $param
     * @return Gb_Form2|boolean
     */
    final public function isPost($param=null)
    {
        if ($param===null) {
            if ($this->_isPost===null) {
                if (isset($_POST["GBFORMPOST"]) && $_POST["GBFORMPOST"]==$this->formHash()) {
                    $this->_isPost=true;
                } else {
                    $this->_isPost=false;
               }
            }
            return $this->_isPost;
        } else {
            $this->_isPost=$param;
            return $this;
        }
    }
    /**
     * Get/set isValid, validate if hasData()
     *
     * @param boolean[optional] $param
     * @return Gb_Form2|boolean|null null if not hasdata
     */
    final public function isValid($param=null)
    {
        if ($param===null) {
            if ($this->_isValid===null) {
                // Si le formulaire a des données, le valider
                if ($this->hasData()) {
                    $this->_isValid=$this->validate(false);
                }
            }
            return $this->_isValid;
        } else {
            $this->_isValid=$param;
            return $this;
        }
    }
    /**
     * get/set method
     * @param string[optional] $text
     * @return Gb_Form2|String
     */
    final public function method($text=null)
    {
        if ($text===null) {         return $this->_method; }
        else { $this->_method=$text; return $this;}
    }
    /**
     * get/set classForm (the class attribute in the <form> tag
     * @param string[optional] $text
     * @return Gb_Form2|String
     */
    final public function classForm($text=null)
    {
        if ($text===null) {         return $this->_classForm; }
        else { $this->_classForm=$text; return $this;}
    }
    /**
     * get/set moreDataRead
     * @param array[optional] $text
     * @return Gb_Form2|array
     */
    final public function moreDataRead(array $text=null)
    {
        if ($text===null) {         return $this->_moreDataRead; }
        else { $this->_moreDataRead=$text; return $this;}
    }
    /**
     * get/set renderFormTags
     * @param boolean[optional] $text
     * @return Gb_Form2|Boolean
     */
    final public function renderFormTags($text=null)
    {
        if ($text===null) {         return $this->_renderFormTags; }
        else { $this->_renderFormTags=$text; return $this;}
    }
    /**
     * get/set formTagOpened
     * @param boolean[optional] $text
     * @return Gb_Form2|Boolean
     */
    final public function formTagOpened($text=null)
    {
        if ($text===null) {         return $this->_formTagOpened; }
        else { $this->_formTagOpened=$text; return $this;}
    }
    /**
     * get/set formPostTagIssued
     * @param boolean[optional] $text
     * @return Gb_Form2|Boolean
     */
    final public function formPostTagIssued($text=null)
    {
        if ($text===null) {         return $this->_formPostTagIssued; }
        else { $this->_formPostTagIssued=$text; return $this;}
    }
    /**
     * get/set formTagClosed
     * @param boolean[optional] $text
     * @return Gb_Form2|Boolean
     */
    final public function formTagClosed($text=null)
    {
        if ($text===null) {         return $this->_formTagClosed; }
        else { $this->_formTagClosed=$text; return $this;}
    }
    /**
     * Set the type of data returned by __toString()
     *
     * @param string $type "HTML" or "JS"
     * @return Gb_Form2|string
     * @throws Gb_Exception
     */
    final public function toStringRendersAs($type=null)
    {
        if ($type===null) { return $this->_toStringRendersAs; }
        $type=strtoupper($type);
        if ($type=="HTML")     {$this->_toStringRendersAs="HTML";}
        elseif ($type=="JS")   {$this->_toStringRendersAs="JS";}
        elseif ($type=="BOTH") {$this->_toStringRendersAs="BOTH";}
        else { throw new Gb_Exception("type $type unhandled"); }
        return $this;
    }
    /**
     * Get/Set the backend
     *
     * @param Gb_Form_Backend_Abstract $p
     * @return Gb_Form2|Gb_Form_Backend_Abstract
     */
    final public function backend(Gb_Form_Backend_Abstract $p=null)
    {
        if ($p===null) {
            return $this->_backend;
        } else {
            $this->_backend=$p;
            $p->setParent($this);
            return $this;
        }
    }
















  /**
   * Remplit les valeurs depuis le backend. Remplit hasData
   *
   * @param array $moreData array("col1", "col2")
   * @return boolean true, null si non applicable, false si pas d'info
   */
    public function getFromDb(array $moreData=array())
    {
        if (null === $this->_backend) {
            return null;
        } else {
            return $this->_backend->getFromDb($moreData);
        }
    }


    /**
     * Renvoie les données stockées sous forme de array
     *
     * @return array array("nom_element"=>"value")
     */
    public function getDataAsArray()
    {
        //@todo: radio, selectmultiple
        // obtient le nom des colonnes
        $aCols=array();
        foreach (new RecursiveIteratorIterator($this->getIterator()) as $elem) {
            $class=get_class($elem);
            if (substr($class, 0, 13)=="Gb_Form_Elem_") {
                $nom=$elem->name();
                $col=$elem->backendCol();
                if (strlen($col)) {
                    $val=$elem->backendValue();
                    $aCols[$nom]=$val;
                }
            }
        }

        return $aCols;
    }


  /**
   * Insère/update les valeurs dans le backend
   *
   * @param array $moreData
   * @return boolean true si tout s'est bien passé
   */
    public function putInDb(array $moreData=array())
    {
        if (null === $this->_backend) {
            return true;
        } else {
            return $this->_backend->putInDb($moreData);
        }
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








   /**
    * Remplit les valeurs depuis $_POST. Remplit hasData
    * @return boolean données trouvées
    */
    public function getFromPost()
    {
        $hasData=false;
        if ($this->isPost()) {
            foreach (new RecursiveIteratorIterator($this->getIterator()) as $elem) {
                $class=get_class($elem);
                if (substr($class, 0, 13)=="Gb_Form_Elem_") {
                    if (!$elem->disabled()) {
                        $name=$elem->elemId();
                        if (isset($_POST[$name])) {
                            $elem->rawvalue($_POST[$name]);
                            $hasData=true;
                            $this->hasData(true);
                        } else {
                            $elem->rawValue(false);
                        }
                    }
                }
            }
        }
        return $hasData;
    }




    /**
     * Valide le formulaire
     * En cas d'erreur, $this->setErrorMsg pour chaque $nom incorrect. Met à jour isValid() et errors()
     *
     * @param boolean $fWrite Affiche les messages d'erreur dans les éléments
     * @return array("nom" => "erreur") ou true si aucune erreur (attention utiliser ===)
     */
    public function validate($fWrite=true)
    {
        $aErrs=array();
        foreach (new RecursiveIteratorIterator($this->getIterator()) as $elem) {
            if (method_exists($elem, "validate") && !$elem->fReadOnly()) {
                $val = $elem->validate($this);
                if (true === $val) {
                    //pas d'erreur
                    if ($fWrite) {$elem->errorMsg("");}
                } elseif (null !== $val) {
                    // erreur
                    $errorMsgCustom=$elem->errorMsgCustom();

                    if (strlen($errorMsgCustom)) {
                        $err=$errorMsgCustom;
                    }
                    if ($fWrite) {$elem->errorMsg($val);}
                    else         {$elem->errorMsg(false);}
                    $aErrs[$elem->name()]=$elem->publicName()." : ".$val;
                }
            }
        }

        $this->errors($aErrs);
        if (count($aErrs)) {
            $this->isValid(false);
            return $aErrs;
        } else {
            $this->isValid(true);
            return true;
        }
    }




    /**
     * Lit les données, les valide et les écrit dans la bdd si elles sont ok
     * Exemple d'utilisation:
     * 		 $res=$myForm->process();
     *       if ($res===true) {
     *           $view->res="OK"; $view->message="Les informations ont bien été enregistrées.";
     *       } elseif ($res===false) {
     *           $view->res="NOK"; $view->message="ERREUR LORS DE L'ENREGISTREMENT. VEUILLEZ NOUS CONTACTER !";
     *       } elseif (is_array($res)) {
     *           $view->res="NOK"; $view->message="Erreur: merci de corriger les informations suivantes:<br />\n";
     *           foreach ($res as $key=>$msg) {
     *               $view->message.="$key: $msg<br />\n";
     *           }
     *       }
     * @param boolean $fWrite Affiche les messages d'erreur dans les éléments
     * @return mixed null si formulaire non soumis, true si formulaire soumis et valide, array si soumis et non valide. false si autre erreur
     */
    public function process($fWrite=true)
    {
        if ($this->load()) {
            if (!$this->isPost()) {
                return null;
            }
            $validate=$this->validate($fWrite);
            if ($validate !== true) {
                return $validate;
            }
            if ($this->putInDb()===true && $this->getFromDb()!==false) {
                return true;
            }
            return false;
        }
        return null;
    }




    /**
     * Lit les données de la db et de post
     * remplit hasData et isLoaded
     * @return boolean si des données ont été lues
     */
    public function load()
    {
        if (!$this->isLoaded()) {
            $getFromDb=$this->getFromDb();
            $getFromPost=$this->getFromPost();
            if ($getFromDb || $getFromPost ) {
                $this->hasData(true);
            } else {
                $this->hasData(false);
            }
            $this->isLoaded(true);
        }
        return $this->hasData();
    }

}
