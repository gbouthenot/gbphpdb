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
    

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que pr�cis�e, ou Gb_Exception
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
    final public function renderHtml()
    {
        $ret="";
        if ($this->renderFormTags()) {
            $ret.=$this->renderFormOpenTag()."\n";
        }
        $ret.=$this->renderFormPostTag()."\n";
        foreach ($this as $elem) {
            if ($elem instanceof Gb_Form_Elem || $elem instanceOf Gb_Form_Group) {
                $ret.=$elem->renderHtml();
            }
        }
        if ($this->renderFormTags()) {
            $ret.=$this->renderFormCloseTag()."\n";
        }
        return $ret;
    }
    final public function renderFormOpenTag()
    {
        $ret="";
        $method=$this->method();   if (strlen($method))  {$method="method='$method'";}
        $action=$this->action();   if (strlen($action))  {$action="action='$action'";}
        $enctype=$this->enctype(); if (strlen($enctype)) {$enctype="enctype='$enctype'";}
        $ret="<form $method $action $enctype>";
        return $ret;
    }
    final public function renderFormPostTag()
    {
        $hash=$this->formHash();
        $ret="<input type='hidden' name='GBFORMPOST' value='$hash' />";
        return $ret;
    }
    final public function renderFormCloseTag()
    {
        return "</form>";
    }
    final public function renderJavascript($fRenderScriptTag=false)
    {
        $ret="";
        foreach ($this as $elem) {
            if ($elem instanceof Gb_Form_Elem || $elem instanceOf Gb_Form_Group) {
                $ret.=$elem->renderJavascript();
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
    final public function render()
    {   $ret="";
        if ($this->_toStringRendersAs=="HTML") {
            $ret=$this->renderHtml();
        } elseif ($this->_toStringRendersAs=="JS") {
            $ret=$this->renderJavascript();
        } elseif ($this->_toStringRendersAs=="BOTH") {
            $ret=$this->renderHtml();
            $ret=$this->renderJavascript();
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
                // Si le formulaire a des donn�es, le valider
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
                // Trie les noms par ordre alphab�tiques si les �l�ments n'ont pas �t� d�finis dans le m�me ordre
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
                // Si le formulaire a des donn�es, le valider
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
     * Renvoie les donn�es stock�es sous forme de array
     *
     * @return array array("nom_element"=>"value")
     */
    public function getDataAsArray()
    {
        //@todo: radio, selectmultiple
        // obient le nom des colonnes
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
   * Ins�re/update les valeurs dans la bdd
   *
   * @param array $moreData
   * @return boolean true si tout s'est bien pass�
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
     * En cas d'erreur, $this->setErrorMsg pour chaque $nom incorrect. Met � jour isValid() et errors()
     *
     * @param boolean $fWrite Affiche les messages d'erreur dans les �l�ments
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
     *
     * @return boolean true si formulaire soumis et valide, false si soumis et non valide. Sinon null.
     */
    public function process()
    {
        if ($this->load()) {
            if (!$this->isPost()) {
                return null;
            }
            if ($this->validate()===true && $this->putInDb()===true && $this->getFromDb()===true) {
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
