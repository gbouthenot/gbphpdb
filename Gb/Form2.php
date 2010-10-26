<?php

require_once("Form/Iterator.php");

/**
 * Gb_Form
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");

Class Gb_Form2 implements IteratorAggregate
{
    protected $_elems;

    //
    // Properties -> these functions are handled by functions named after the properties name i.e. action() sets the _action
    //
    protected $_action;
    protected $_enctype="www/form-data";
    protected $_errors;
    protected $_formHash;
    protected $_hasData;
    protected $_isLoaded;
    protected $_isPost;
    protected $_isValid;
    protected $_method="post";
    protected $_moreData=array();
    protected $_moreDataRead=array();
    protected $_renderFormTags=true;
    protected $_toStringRendersAs="HTML";
    
    protected $_formTagOpened=false;
    protected $_formPostTagIssued=false;
    protected $_formTagClosed=false;

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
      
    /**
     * constructeur
     *
     * @param array[optional] $aParams
     */
    public function __construct($aParams=array())
    {
        $this->_elems=array();
        $availableParams=array(
            "action", "enctype", "errors", "formHash", "hasData",
            "isLoaded", "isPost", "isValid",
            "method", "moreData", "moreDataRead", "renderFormTags", "toStringRendersAs",
            "formTagOpened", "formPostTagIssued", "formTagClosed" ,
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
        return (new Gb_Form_Iterator($this));
    }
// implements InteratorAggregate END

// implements standard OOP START
    final public function __set($key, $val)
    {
        $this->_elems[$key]=$val;
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
            $this->_elems[]=$arg;
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
                if ($elemOrGroup instanceof Gb_Form_Elem || $elemOrGroup instanceOf Gb_Form_Group) {
                    $ret.=$elemOrGroup->renderHtml();
                }
            }
        } else {
            if (is_string($aElemNames)) {
                $aElemNames=array($aElemNames);
            }
            foreach ($aElemNames as $elemname) {
                $elemOrGroup=$this->getElem($elemname);
                if ($elemOrGroup instanceof Gb_Form_Elem || $elemOrGroup instanceOf Gb_Form_Group) {
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
            $ret="<form $method $action $enctype>";
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
        $ret="";

        if ($aElemNames===null) {
            foreach ($this as $elemOrGroup) {
                if ($elemOrGroup instanceof Gb_Form_Elem || $elemOrGroup instanceOf Gb_Form_Group) {
                    $ret.=$elemOrGroup->renderJavascript();
                }
            }
        } else {
            if (is_string($aElemNames)) {
                $aElemNames=array($aElemNames);
            }
            foreach ($aElemNames as $elemname) {
                $elemOrGroup=$this->getElem($elemname);
                if ($elemOrGroup instanceof Gb_Form_Elem || $elemOrGroup instanceOf Gb_Form_Group) {
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
            $ret=$this->renderJavascript($aElemNames, true);
        }
        return $ret;
    }
    final public function getAjaxArgs()
    {
        $hash=$this->formHash();
        $ret="GBFORMPOST: '$hash'";
        
        foreach ($this as $elem) {
            if ($elem instanceof Gb_Form_Elem || $elem instanceof Gb_Form_Group) {
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
     * get/set moreData
     * @param array[optional] $text
     * @return Gb_Form2|array 
     */
    final public function moreData(array $text=null)
    {   
        if ($text===null) {         return $this->_moreData; }
        else { $this->_moreData=$text; return $this;}
    }
    /**
     * get/set moreDataRead
     * @param array[optional] $text
     * @return Gb_Form2|array 
     */
    final public function moreDataRead(array $text=null)
    {   
        if ($text===null) {         return $this->_moreData; }
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
   * Remplit les valeurs depuis la base de données
   *
   * @return boolean true si données trouvées
   */
    public function getFromDb()
    {
        return true;
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
   * Insère/update les valeurs dans la bdd
   *
   * @param array $moreData
   * @return boolean true si tout s'est bien passé
   */
    public function putInDb(array $moreData=array())
    {
        $moreData;
        return true;
    }
    
    
    /**
     * get a elem by name
     *
     * @param string $name
     * @return Gb_Form_Elem
     * @throws Gb_Exception
     */
    public function getElem($name)
    {
        foreach (new RecursiveIteratorIterator($this->getIterator()) as $elem) {
            $c=get_class($elem);
            if (substr($c, 0, 13)=="Gb_Form_Elem_") {
                if ($elem->name()==$name) {
                    return $elem;
                }
            }
        }
        throw new Gb_Exception("Element $name not found");
    }
    

    
    
    
    
    

   /**
    * Remplit les valeurs depuis $_POST
    * @return Gb_Form
    */
    public function getFromPost()
    {
        if ($this->isPost()) {
            foreach (new RecursiveIteratorIterator($this->getIterator()) as $elem) {
                $class=get_class($elem);
                if (substr($class, 0, 13)=="Gb_Form_Elem_") {
                    if (!$elem->disabled()) {
                        $name=$elem->elemId();
                        if (isset($_POST[$name])) {
                            $elem->rawValue($_POST[$name]);
                            $this->hasData(true);
                        } else {
                            $elem->rawValue(false);
                        }
                    }
                }
            }
        }
        return $this;
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
            if (method_exists($elem, "validate")) {
                $err=$elem->validate($this);
                if ($err===true) {
                    if ($fWrite) {$elem->errorMsg("");}
                } elseif ($err!==null) {
                    $errorMsgCustom=$elem->errorMsgCustom();
                    if (strlen($errorMsgCustom)) {
                        $err=$errorMsgCustom;
                    }
                    if ($fWrite) {$elem->errorMsg($err);}
                    $aErrs[$elem->name()]=$elem->publicName()." : ".$err;
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
    
    
    
    
    
    
    public function getMoreDataRead()
    {
        return $this->_moreDataRead;
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
     *
     * @return mixed null si formulaire non soumis, true si formulaire soumis et valide, array si soumis et non valide. false si autre erreur
     */
    public function process()
    {
        if ($this->load()) {
            if (!$this->isPost()) {
                return null;
            }
            $validate=$this->validate();
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
     * 
     * @return boolean si des données ont été lues
     *
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
