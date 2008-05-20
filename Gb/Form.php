<?php
/**
 * 
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Db.php");
require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."String.php");
require_once(_GB_PATH."Util.php");

Class Gb_Form
{
  protected $formElements=array();

  /**
   * @var Gb_Db
   */
  protected $db;
  protected $where;
  protected $tableName;

  protected static $fPostIndicator=false;

  protected $fValid;
  protected $fLoaded;
  protected $fHasData;
  protected $fPost;
  protected $aErrors;

  protected $_commonRegex = array(
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
    'Year20xx'        => '/^(20[0-9]{2})$/',          // aaaa 2000<=aaaa<=2099
    'DateFr'          => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/(((19)|(20))[0-9]{2})$/', // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   1900<=aaaa<=2099
    'DateFr20xx'      => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/($20[0-9]{2})/',          // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   2000<=aaaa<=2099
  );


  /**
   * constructeur de Gb_Form
   *
   * @param Gb_Db[optional] $db
   * @param string[optional] $tableName si vide, pas de bdd
   * @param array[optional] $where array(condition, array("usa_login='?'", "gbo"), ...)
   */
  public function __construct(Gb_Db $db=null, $tableName="", array $where=array())
  {
    $this->db=$db;
    $this->tableName=$tableName;
    
/*    // transforme la condition avec quoteInto, si nécéssaire
    $where2=array();
    foreach ($where as $cond) {
      if (is_array($cond)) {
        $str=array_shift(&$cond);
        $cond=$db->quoteInto($str, $cond);
      }
      $where2[]=$cond;
    }
    $this->where=$where2;
*/
    $this->where=$where;
  }

  /**
   * Ajoute un élément
   *
   * @param string $nom Nom unique, défini le NAME de l'élément doit commencer par une lettre et ne comporter que des caractères alphanumériques
   * @param array $aParams
   * @throws Gb_Exception
   */
  public function addElement($nom, array $aParams)
  {
        // $aParams["type"]="SELECT" "SELECTMULTIPLE" "TEXT" "PASSWORD" "RADIO" "CHECKBOX" "TEXTAREA"
        // $aParams["args"]:
        //            SELECT: liste des valeurs disponibles sous la forme
        //                    array(array(value[,libelle]), "default"=>array(value[,libelle]), ...)
        //                    (value est recodé dans le html mais renvoie la bonne valeur)
        //                    (si value==='false', la valeur est interdite par fMandatory)
        //                    (si value==='optgroup', la valeur définit un optgroup
        //    SELECTMULTIPLE: idem SELECT mais sans la possibilité d'avoir un default 
        //              TEXT: array("regexp"=>"/.*/" ou "Year" pour prédéfini) 
        //          TEXTAREA: idem TEXT
        //            HIDDEN:
        //          CHECKBOX:
        //             RADIO:
        // $aParams["dbCol"]       : nom de la colonne bdd
        // $aParams["fMandatory"]  : doit être rempli ? défaut: false
        // $aParams["toDbFunc"]    : array( "fonction" ou array("classe", "methode") , array("%s", ENT_QUOTES)[optional] ) 
        // $aParams["fromDbFunc"]  : array( "fonction" ou array("classe", "methode") , array("%s", ENT_QUOTES)[optional] ) 
        // $aParams["invalidMsg"]  : texte qui s'affiche en cas de saisie invalide
        // $aParams["classOK"]     : nom de la classe pour élément valide défaut: GBFORM_OK
        // $aParams["classNOK"]    : nom de la classe pour élément non valide défaut: GBFORM_NOK
        // $aParams["classERROR"]  : nom de la classe pour erreur: GBFORM_ERROR
        // $aParams["preInput"]    :
        // $aParams["inInput"]     : pour TEXT: size et maxlength
        // $aParams["postInput"]   :
        // renseignés automatiquement (accessible uniquement en lecture):
        // $aParams["class"]       : nom de la classe en cours
        // $aParams["message"]    : message d'erreur éventuel
      
    if (!preg_match("/^[a-zA-Z][a-zA-Z0-9]*/", $nom))
      throw new Gb_Exception("Nom de variable de formulaire invalide");

    if (isset($this->formElement[$nom]))
      throw new Gb_Exception("Nom de variable de formulaire déjà défini");

    if (!isset($aParams["type"]))
      throw new Gb_Exception("Type de variable de formulaire non précisé");

    if (!isset($aParams["fMandatory"]))
      $aParams["fMandatory"]=false;
    if (!isset($aParams["preInput"]))
      $aParams["preInput"]="";
    if (!isset($aParams["inInput"]))
      $aParams["inInput"]="";
    if (!isset($aParams["postInput"]))
      $aParams["postInput"]="";
    if (!isset($aParams["classOK"]))
      $aParams["classOK"]="GBFORM_OK";
    if (!isset($aParams["classNOK"]))
      $aParams["classNOK"]="GBFORM_NOK";
    if (!isset($aParams["classERROR"]))
      $aParams["classERROR"]="GBFORM_ERROR";
    if (!isset($aParams["class"]))
      $aParams["class"]=$aParams["classOK"];

    if (isset($aParams["toDbFunc"]))
      $aParams["toDbFunc"]=$aParams["toDbFunc"];
    if (isset($aParams["fromDbFunc"]))
      $aParams["fromDbFunc"]=$aParams["fromDbFunc"];
      
      $aParams["message"]="";

    $type=$aParams["type"];
    switch($type)
    {
      case "SELECT":
        if (!isset($aParams["args"]) || !is_array($aParams["args"]))
          throw new Gb_Exception("Paramètres de $nom incorrects");
        //remplit value avec le numéro sélectionné.
        $num=0;
        $args=array();
        foreach($aParams["args"] as $ordre=>$val) {
          if ($ordre==="default") {
            $aParams["value"]=$num;
          }
          $args[]=$val;
          $num++;
        }
        $aParams["args"]=$args;
        if (!isset($aParams["value"])) {
            $aParams["value"]="0";    // par défaut, 1er élément de la liste
        }
        $this->formElements[$nom]=$aParams;
        break;

      case "SELECTMULTIPLE":
        if (!isset($aParams["args"]) || !is_array($aParams["args"]))
          throw new Gb_Exception("Paramètres de $nom incorrects");
        if (!isset($aParams["value"]))
          $aParams["value"]=array();    // par défaut, aucune selection
        $this->formElements[$nom]=$aParams;
        break;

      case "TEXT": case "TEXTAREA":
        if (isset($aParams["args"]["regexp"])){
          $regexp=&$aParams["args"]["regexp"];
          if (isset($this->_commonRegex[$regexp])) {
            //regexp connu: remplace par le contenu
            $regexp=$this->_commonRegex[$regexp];
          }
        }
        if (!isset($aParams["value"]))
          $aParams["value"]="";   // par défaut, chaine vide
        $this->formElements[$nom]=$aParams;
        break;

      case "CHECKBOX":
        if (isset($aParams["value"]) && $aParams["value"]==true)
          $aParams["value"]=true;
        else
          $aParams["value"]=false;
        $this->formElements[$nom]=$aParams;
        break;

      case "RADIO": case "HIDDEN":
        if (!isset($aParams["value"]))
          $aParams["value"]="";   // par défaut, chaine vide
        $this->formElements[$nom]=$aParams;
        break;

        default:
        throw new Gb_Exception("Type de variable de formulaire inconnu pour $nom");
    }

  }

  public function getElementParam($nom, $paramName)
  {
    if (!isset($this->formElements[$nom]))
      throw new Gb_Exception("Get elementParam: nom=$nom non défini");
    $aElement=$this->formElements[$nom];
    if (isset($aElement[$paramName]))
      return $aElement[$paramName];
    else
      return false;
  }

  public function setElementParam($nom, $paramName, $value)
  {
    if (!isset($this->formElements[$nom]))
      throw new Gb_Exception("Set elementParam: nom=$nom non défini");
    $this->formElements[$nom][$paramName]=$value;
  }


    public function set($nom, $value)
    {
        if (!isset($this->formElements[$nom]))
            throw new Gb_Exception("Set impossible: nom=$nom non défini");

        $type=$this->formElements[$nom]["type"];
        if ($type=="SELECT") {
            foreach ($this->formElements[$nom]["args"] as $ordre=>$val) {
                if ($val[0]===$value) {
                    $value=$ordre;
                    break;
                }
                 
            }
        } else if ($type=="CHECKBOX") {
            $value= ($value) ? (true) : (false);
        }
        $this->formElements[$nom]["value"]=$value;
    }


  public function get($nom)
  {
    if (!isset($this->formElements[$nom]))
      throw new Gb_Exception("Set impossible: nom=$nom non défini");

    $value=$this->formElements[$nom]["value"];

    if ($this->formElements[$nom]["type"]=="SELECT" && isset($this->formElements[$nom]["args"][$value])) {
      $value=$this->formElements[$nom]["args"][$value][0];
    }
    return $value;
  }

  /**
   * Renvoit le code HTML approprié (valeur par défaut, préselectionné, etc)
   *
   * @param string[optional] $nom
   * @param string[optional] $radioValue
   * @throws Gb_Exception
   */
  public function getHtml($nom="", $radioValue="")
  {
    $ret="";

    // si nom vide, rappelle la fonction pour tous les éléments
    if ($nom==="") {
      foreach ($this->formElements as $nom=>$aElement) {
        $ret.=$this->getHtml($nom);
      }
      return $ret;
    }

    if (!isset($this->formElements[$nom])) {
      throw new Gb_Exception("Variable de formulaire inexistante");
    }

    if (self::$fPostIndicator==false) {
      // positionnement de la variable statique indiquand que l'indicateur a été mis.
      $ret.="<input type='hidden' name='GBFORMPOST' value='true' />\n";
      self::$fPostIndicator=true;
    }

    $aElement=$this->formElements[$nom];
    $class=$aElement["class"];

    $type=$aElement["type"];
    $value=$aElement["value"];
    switch ($type) {
      case "SELECT":
        $ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
        $ret.="<label>".$aElement["preInput"];
        $aValues=$aElement["args"];
        $html=$aElement["inInput"];
        $ret.="<select id='GBFORM_$nom' name='GBFORM_$nom' $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();'>\n";
        $num=0;
        $fOptgroup=false;
        foreach ($aValues as $ordre=>$aOption){
          $sVal=htmlspecialchars($aOption[0], ENT_QUOTES);
          $sLib=htmlspecialchars(!empty($aOption[1])?$aOption[1]:$aOption[0], ENT_QUOTES);
          if ($sVal=="optgroup") {
              if ($fOptgroup) {
                  $ret.="</optgroup>\n";
              }
              $ret.="<optgroup label='$sLib'>\n";
              $fOptgroup=true;
          } else {
              $sSelected="";
              if ($ordre==$value)
                $sSelected="selected='selected'";
              $ret.="<option value='$num' $sSelected>$sLib</option>\n";
          }
          $num++;
        }
        if ($fOptgroup) {
            $ret.="</optgroup>\n";
        }
        $ret.="</select>\n";
        break;

      case "SELECTMULTIPLE":
        $ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
        $ret.="<label>".$aElement["preInput"];
        $aValues=$aElement["args"];
        $html=$aElement["inInput"];
        $ret.="<select multiple='multiple' id='GBFORM_$nom' name='GBFORM_{$nom}[]' $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();'>\n";
        foreach ($aValues as $ordre=>$aOption){
          $sVal=htmlspecialchars($aOption[0], ENT_QUOTES);
          $sLib=htmlspecialchars($aOption[1], ENT_QUOTES);
          $sSelected="";
          if (in_array($sVal,$value))
            $sSelected="selected='selected'";
          $ret.="<option value='$sVal' $sSelected>$sLib</option>\n";
        }
        $ret.="</select>\n";
        break;

      case "TEXT": case "PASSWORD":
        $ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
        $ret.="<label>".$aElement["preInput"];
        $html=$aElement["inInput"];
        $sValue=htmlspecialchars($value, ENT_QUOTES);
        $ret.="<input type='".strtolower($type)."' class='text' id='GBFORM_$nom' name='GBFORM_$nom' $html value='$sValue' onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
        break;

      case "HIDDEN":
        $ret.="<label>".$aElement["preInput"];
        $html=$aElement["inInput"];
        $sValue=htmlspecialchars($value, ENT_QUOTES);
        $ret.="<input type='".strtolower($type)."' id='GBFORM_$nom' name='GBFORM_$nom' $html value='$sValue' />\n";
        break;

      case "TEXTAREA":
        $ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
        $ret.="<label>".$aElement["preInput"];
        $html=$aElement["inInput"];
        $sValue=htmlspecialchars($value, ENT_QUOTES);
        $ret.="<textarea class='textarea' id='GBFORM_$nom' name='GBFORM_$nom' $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();'>$sValue</textarea>\n";
        break;

      case "CHECKBOX":
        $ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
        $ret.="<label>".$aElement["preInput"];
        $sValue="";
        if ($value==true)
          $sValue=" checked='checked'";
        $html=$aElement["inInput"];
        $ret.="<input type='checkbox' class='checkbox' id='GBFORM_$nom' name='GBFORM_$nom' value='true' $sValue $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
        break;

      case "RADIO":
        $ret.="<div class='$class'>\n";
        $ret.="<label>".$aElement["preInput"];
        $sValue="";
        if ($value==$radioValue)
          $sValue=" checked='checked'";
        $html=$aElement["inInput"];
        $ret.="<input type='radio' class='radio' id='GBFORM_$nom' name='GBFORM_$nom' value='$radioValue' $sValue $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
        break;

      default:
        throw new Gb_Exception("Type inconnu");
    }
        $ret.=$aElement["postInput"]."</label>";
    $errorMsg=$aElement["message"];
    if (strlen($errorMsg)) {
      $class=$aElement["classERROR"];
      $ret.="<div class='$class'>$errorMsg</div>";
    }
    if ($type!="HIDDEN") {
      $ret.="</div>\n";
    }

    return $ret;
  }


  /**
   * Change la classe d'un elément
   *
   * @param string $nom
   * @param boolean|string $class : false/true: met a classNOK/classOK string: met a la classe spécifiée
   * @throws Gb_Exception
   */
  public function setClass($nom, $class=false)
  {
    if (!isset($this->formElements[$nom]))
      throw new Gb_Exception("Element de fomulaire non défini");
    if ($class===false) {
      $class=$this->formElements[$nom]["classNOK"];
    } elseif ($class===true) {
      $class=$this->formElements[$nom]["classOK"];
    }

    $this->formElements[$nom]["class"]=$class;
  }



  /**
   * Change le message d'erreur d'un elément
   *
   * @param string $nom
   * @param string[optional] $errorMsg
   * @throws Gb_Exception
   */
  public function setErrorMsg($nom, $errorMsg="")
  {
    if (!isset($this->formElements[$nom]))
      throw new Gb_Exception("Element de fomulaire non défini");
    $this->formElements[$nom]["message"]=$errorMsg;
  }


  /**
   * Renvoie le code javascript pour la validation dynamique
   *
   * @param string[optinal] $nom élément à récupérér ou vide pour tous
   * @return string
   */
  public function getJavascript($nom="")
  {
    $ret="";

    // si nom vide, rappelle la fonction pour tous les éléments
    if ($nom==="") {
      foreach ($this->formElements as $nom=>$aElement) {
        $ret.=$this->getJavascript($nom);
      }
        return $ret;
    }

    if (!isset($this->formElements[$nom])) {
      throw new Gb_Exception("Variable de formulaire inexistante");
    }
    $aElement=$this->formElements[$nom];

    $type=$aElement["type"];
    switch ($type) {
      case "SELECTMULTIPLE": case "HIDDEN":
        break;

      case "SELECT":
        // par défaut, met en classOK, si erreur, repasse en classNOK
        $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
        // enlève le message d'erreur
        $ret.=" var e=\$('GBFORM_{$nom}_div').select('div[class=\"GBFORM_ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";

        // attention utilise prototype String.strip()
        $ret.="var value=remove_accents(\$F('GBFORM_$nom').strip());\n";
        if ($aElement["fMandatory"]) {
          $aValues="";
          foreach($aElement["args"] as $ordre=>$val) {
            $val=htmlspecialchars($val[0], ENT_QUOTES);
            if ($val===false) $val="false";
            $aValues[]="'$ordre':'$val'";
          }
          $ret.="var GBFORM_{$nom}_values = { ".implode(", ",$aValues)."};\n";
          $ret.="if ((GBFORM_{$nom}_values[value])=='false') {\n";
          $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
          $ret.="}\n";
        }
        if (isset($aElement["NOTVALUE"])){
          $aNotValues=$aElement["NOTVALUE"];
          if (!is_array($aNotValues)) $aNotValues=array($aNotValues);
          foreach ($aNotValues as $notValue) {
            $ret.=" var bornevalue=value;\n";
            if (strpos($notValue, "GBFORM_")===0) {
              // borne commence par GBFORM_
              $notValue="\$F('$notValue')";
            }
            $ret.=" var notvalue=eval({$notValue});\n";
            $ret.=" if (bornevalue == notvalue) {";
            $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
            $ret.="}\n";
          }
        }
        break;

      case "TEXT": case "PASSWORD": case "TEXTAREA":
        // par défaut, met en classOK, si erreur, repasse en classNOK
        $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
        // enlève le message d'erreur
        $ret.=" var e=\$('GBFORM_{$nom}_div').select('div[class=\"GBFORM_ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        
        // attention utilise prototype String.strip()
        $ret.="var value=remove_accents(\$F('GBFORM_$nom').strip());\n";
        if ($aElement["fMandatory"]) {
          $ret.="if (value=='') {\n";
          $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
          $ret.="}\n";
        }
        if (isset($aElement["args"]["regexp"])){
          $regexp=$aElement["args"]["regexp"];
          if (isset($this->_commonRegex[$regexp])) {
            //regexp connu: remplace par le contenu
            $regexp=$this->_commonRegex[$regexp];
          }
          $ret.="var regexp=$regexp\n";
          $ret.="if (!regexp.test(value)) {\n";
          $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
          $ret.="}\n";
        }
        if (isset($aElement["args"]["minvalue"])){
          $aMinValues=$aElement["args"]["minvalue"];
                    if (!is_array($aMinValues)) $aMinValues=array($aMinValues);
          foreach ($aMinValues as $borne) {
            if (is_array($borne) && isset($aElement["args"]["regexp"])) {
              // si array, alors extrait la valeur du regexp avant de comparer
              $ret.=" var bornevalue=value.replace({$aElement["args"]["regexp"]}, \"{$borne[0]}\");\n";
              $borne=$borne[1];
            }
            else {
              $ret.=" var bornevalue=value;\n";
            }
            if (strpos($borne, "GBFORM_")===0) {
              // borne commence par GBFORM_
              $borne="\$F('$borne')";
            }
            $ret.=" var borne=eval({$borne});\n";
            $ret.=" if (bornevalue < borne) {";
            $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
            $ret.="}\n";
          }
        }
        if (isset($aElement["args"]["maxvalue"])){
          $aMaxValues=$aElement["args"]["maxvalue"];
                    if (!is_array($aMaxValues)) $aMaxValues=array($aMaxValues);
          foreach ($aMaxValues as $borne) {
            if (is_array($borne) && isset($aElement["args"]["regexp"])) {
              // si array, alors extrait la valeur du regexp avant de comparer
              $ret.=" var bornevalue=value.replace({$aElement["args"]["regexp"]}, \"{$borne[0]}\");\n";
              $borne=$borne[1];
            }
            else {
              $ret.=" var bornevalue=value;\n";
            }
            if (strpos($borne, "GBFORM_")===0) {
              // borne commence par GBFORM_
              $borne="\$F('$borne')";
            }
            $ret.=" var borne=eval({$borne});\n";
            $ret.=" if (bornevalue > borne) {";
            $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
            $ret.="}\n";
          }
        }
        if (isset($aElement["args"]["notvalue"])){
          $aNotValues=$aElement["args"]["notvalue"];
                    if (!is_array($aNotValues)) $aNotValues=array($aNotValues);
          foreach ($aNotValues as $notValue) {
            if (is_array($notValue) && isset($aElement["args"]["regexp"])) {
              // si array, alors extrait la valeur du regexp avant de comparer
              $ret.=" var bornevalue=value.replace({$aElement["args"]["regexp"]}, \"{$notValue[0]}\");\n";
              $notValue=$notValue[1];
            }
            else {
              $ret.=" var bornevalue=value;\n";
            }
            if (strpos($notValue, "GBFORM_")===0) {
              // borne commence par GBFORM_
              $notValue="\$F('$notValue')";
            }
            $ret.=" var notvalue=eval({$notValue});\n";
            $ret.=" if (bornevalue == notvalue) {";
            $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
            $ret.="}\n";
          }
        }
        break;

      case "CHECKBOX":
          // par défaut, met en classOK, si erreur, repasse en classNOK
          $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
          // enlève le message d'erreur
          $ret.=" var e=\$('GBFORM_{$nom}_div').select('div[class=\"GBFORM_ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
          
          if ($aElement["fMandatory"]) {
              $ret.="var value=\$F('GBFORM_$nom');\n";
              $ret.="if (value!='true') {\n";
              $ret.=" \$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
              $ret.="}\n";
          }
        break;

      case "RADIO":
        break;

      default:
        throw new Gb_Exception("Type inconnu");
    }

    if (strlen($ret)) {
      $ret2="function validate_GBFORM_$nom()\n";
      $ret2.="{\n";
      $ret2.=$ret;
      $ret2.="}\n";
      return $ret2;
    }

    return "";
  }

  /**
   * Remplit les valeurs depuis la base de données
   *
   * @return boolean true si données trouvées
   */
  public function getFromDb()
  {
    if ($this->db===null || count($this->where)==0)
      return false;

    //todo: checkbox
    // obient le nom des colonnes
    $aCols=array();
    foreach ($this->formElements as $nom=>$aElement) {
      if (isset($aElement["dbCol"])) {
        $aCols[$nom]=$aElement["dbCol"];
      }
    }

    if (strlen($this->tableName)==0 || count($aCols)==0) {
      return false;
    }

    $sql="SELECT ".implode(", ", $aCols)." FROM ".$this->tableName;
    if (count($this->where)) {
      $sql.=" WHERE";
      $sWhere="";
      foreach ($this->where as $w)
      { if (strlen($sWhere)) {
          $sWhere.=" AND";
        }
        $sWhere.=" $w";
      }
      $sql.=$sWhere;
    }

    $db=$this->db;
    $aLigne=$db->retrieve_one($sql);
    if ($aLigne===false) {
    // La requête n'a pas renvoyé de ligne
      return false;
    }

    // La requête a renvoyé une ligne
    foreach ($aCols as $nom=>$dbcol) {
        $aElement=$this->formElements[$nom];
        $val=$aLigne[$dbcol];
        // regarde si une fonction est fournie pour transformer avant de mettre dans la db 
        if (isset($aElement["fromDbFunc"])) {
            $func=$aElement["fromDbFunc"][0];
            $params=$aElement["fromDbFunc"][1];
            foreach ($params as &$param) {
                if (is_string($param)) {
                    $param=sprintf($param, $val);
                }
            }
            $val=call_user_func_array($func, $params);
        }
        
        $this->set($nom, $val);
    }
    return true;
  }


  /**
   * Insère/update les valeurs dans la bdd
   *
   * @param array $moreData
   * @return boolean true si tout s'est bien passé
   */
    public function putInDb(array $moreData=array())
    {
        if ($this->db===null) {
            return true;
        }
    
        //@todo: checkbox, radio, selectmultiple
        // obient le nom des colonnes
        $aCols=$moreData;
        foreach ($this->formElements as $nom=>$aElement) {
            if (isset($aElement["dbCol"])) {
                $col=$aElement["dbCol"];
                $type=$aElement["type"];
                $val=$this->get($nom);
                if ($type=="CHECKBOX") {
                    $val= ($val) ? (1):(0);
                }
                // regarde si une fonction est fournie pour transformer avant de mettre dans la db 
                if (isset($aElement["toDbFunc"])) {
                    $func=$aElement["toDbFunc"][0];
                    $params=$aElement["toDbFunc"][1];
                    foreach ($params as &$param) {
                        if (is_string($param)) {
                            $param=sprintf($param, $val);
                        }
                    }
                    $val=call_user_func_array($func, $params);
                }

                $aCols[$col]=$val;
            }
        }
    
        if (strlen($this->tableName)==0 || count($aCols)==0) {
            return false;
        }

        $db=$this->db;
        try {
            if (count($this->where)) {
                // il y a une condition where: fait un replace
                $db->replace($this->tableName, $aCols, $this->where);
            } else {
                // pas de where: fait insert
                $db->insert($this->tableName, $aCols);
            }
                
        } catch (Exception $e) {
            $e;
            Gb_Log::Log(Gb_Log::LOG_ERROR, "GBFORM->putInDb ERROR table:{$this->tableName} where:".Gb_Log::Dump($this->where)." data:".Gb_Log::Dump($aCols) );
            return false;
        }

        Gb_Log::Log(Gb_Log::LOG_INFO, "GBFORM->putInDb OK table:{$this->tableName} where:".Gb_Log::Dump($this->where)."" );
        return true;
    }
    
    

   /**
    * Remplit les valeurs depuis $_POST
    * @return true si données trouvées
    */
    public function getFromPost()
    {
        if ($this->fPost===null) {
            $fPost=false;
            if (isset($_POST["GBFORMPOST"])) {
                // detecte que le formulaire a été soumis. Utile pour les checkbox
                $fPost=true;
            }
            foreach ($this->formElements as $nom=>$aElement) {
                $type=$aElement["type"];
                if ($fPost && $type=="CHECKBOX") {
                    // met les checkbox à false
                    $this->formElements[$nom]["value"]=false;
            }
            if (isset($_POST["GBFORM_".$nom])) {
                $this->formElements[$nom]["value"]=$_POST["GBFORM_".$nom];
                $fPost=true;
            }
          }
          $this->fPost=$fPost;
        }
        return $this->fPost;
    }
    
    
    
    
  /**
   * Valide le formulaire
   * En cas d'erreur, appelle $this->setClass() et $this->setErrorMsg pour chaque $nom incorrect
   *
   * @return array("nom" => "erreur") ou true si aucune erreur (attention utiliser ===)
   */
  public function validate()
  {
    $aErrs=array();
    foreach ($this->formElements as $nom=>$aElement) {
        //pour tous les éléments:
      $type=$aElement["type"];

      switch ($type) {
        case "SELECT":
          $value=strtolower(Gb_String::mystrtoupper(trim($aElement["value"])));
          // Vérifie que la valeur est bien dans la liste et maj $value
          if (isset($aElement["args"][$value])) {
            $value=$aElement["args"][$value][0];
          } else {
            $aErrs[$nom]="Choix invalide";
            continue;
          }
          if ($value===false) {
            $aErrs[$nom]="Choix invalide";
            continue;
          }
              
          if (strlen($value) && isset($aElement["NOTVALUE"])) {
            $aBornes=$aElement["NOTVALUE"];
                        if (!is_array($aBornes)) $aBornes=array($aBornes);
              foreach ($aBornes as $borne) {
              $bornevalue=$value;
              $sBorne=$borne;
              if (strpos($borne, "GBFORM_")===0) {
                // borne commence par GBFORM_
                $sBorne=substr($borne,7);
                $borne=$this->get($sBorne);
                $sBorne.=" ($borne)";
              }
              if ($bornevalue == $borne) {
                $aErrs[$nom]="Doit être différent de $sBorne";
                continue;
              }
            }
          }
          break;

        case "SELECTMULTIPLE": case "HIDDEN":
        break;

        case "RADIO": case "CHECKBOX":
          $value=strtolower(Gb_String::mystrtoupper(trim($aElement["value"])));
          break;

        case "TEXT": case "PASSWORD": case "TEXTAREA":
          $value=strtolower(Gb_String::mystrtoupper(trim($aElement["value"])));
          if (strlen($value) && isset($aElement["args"]["regexp"])) {
            $regexp=$aElement["args"]["regexp"];
            if (!preg_match($regexp, $value)) {
              $aErrs[$nom]="Valeur incorrecte";
              continue;
            }
          }
          if (strlen($value) && isset($aElement["args"]["minvalue"])) {
            $aBornes=$aElement["args"]["minvalue"];
                        if (!is_array($aBornes)) $aBornes=array($aBornes);
            foreach ($aBornes as $borne) {
              $bornevalue=$value;
              if (is_array($borne) && isset($aElement["args"]["regexp"])) {
                // si array, alors extrait la valeur du regexp avant de comparer
                $bornevalue=preg_replace( $aElement["args"]["regexp"],$borne[0], $value);
                $borne=$borne[1];
              }
              $sBorne=$borne;
              if (strpos($borne, "GBFORM_")===0) {
                // borne commence par GBFORM_
                $sBorne=substr($borne,7);
                $borne=$this->get($sBorne);
                $sBorne.=" ($borne)";
              }
              if ($bornevalue < $borne) {
                $aErrs[$nom]="Doit être supérieur ou égal à $sBorne";
                continue;
              }
            }
          }
          if (strlen($value) && isset($aElement["args"]["maxvalue"])) {
            $aBornes=$aElement["args"]["maxvalue"];
            if (!is_array($aBornes)) $aBornes=array($aBornes);
            foreach ($aBornes as $borne) {
              $bornevalue=$value;
              if (is_array($borne) && isset($aElement["args"]["regexp"])) {
                // si array, alors extrait la valeur du regexp avant de comparer
                $bornevalue=preg_replace( $aElement["args"]["regexp"],$borne[0], $value);
                $borne=$borne[1];
              }
              $sBorne=$borne;
              if (strpos($borne, "GBFORM_")===0) {
                // borne commence par GBFORM_
                $sBorne=substr($borne,7);
                $borne=$this->get($sBorne);
                $sBorne.=" ($borne)";
              }
              if ($bornevalue > $borne) {
                $aErrs[$nom]="Doit être inférieur ou égal à $sBorne";
                continue;
              }
            }
          }
          if (strlen($value) && isset($aElement["args"]["notvalue"])) {
            $aBornes=$aElement["args"]["notvalue"];
                        if (!is_array($aBornes)) $aBornes=array($aBornes);
            foreach ($aBornes as $borne) {
              $bornevalue=$value;
              if (is_array($borne) && isset($aElement["args"]["regexp"])) {
                // si array, alors extrait la valeur du regexp avant de comparer
                $bornevalue=preg_replace( $aElement["args"]["regexp"],$borne[0], $value);
                $borne=$borne[1];
              }
              $sBorne=$borne;
              if (strpos($borne, "GBFORM_")===0) {
                // borne commence par GBFORM_
                $sBorne=substr($borne,7);
                $borne=$this->get($sBorne);
                $sBorne.=" ($borne)";
              }
              if ($bornevalue == $borne) {
                $aErrs[$nom]="Doit être différent de $sBorne";
                continue;
              }
            }
          }


      }

      if ($aElement["fMandatory"]) {
        // Vérifie que le champ et bien rempli
        if ( (($type!="SELECT" && $type!="SELECTMULTIPLE") && strlen($value)==0) ) {
          if ($type=="SELECT")        $aErrs[$nom]="Aucun choix sélectionné";
          elseif ($type=="TEXT")      $aErrs[$nom]="Valeur non renseignée";
          elseif ($type=="TEXTAREA")  $aErrs[$nom]="Texte non renseigné";
          elseif ($type=="CHECKBOX")  $aErrs[$nom]="Case non cochée";
          elseif ($type=="RADIO")     $aErrs[$nom]="?";
          elseif ($type=="PASSWORD")  $aErrs[$nom]="Mot de passe vide";
          else                        $aErrs[$nom]="Champ non renseigné";
          continue;
        }
      }
    }//foreach ($this->formElements as $nom=>$aElement) {

    foreach($aErrs as $nom=>$reason)
    {
      $this->setClass($nom, false);
      if (!empty($this->formElements[$nom]["invalidMsg"]))
       $reason=$this->formElements[$nom]["invalidMsg"];
      $this->setErrorMsg($nom, $reason);
    }

    $this->aErrors=$aErrs;
    if (count($aErrs)==0) {
        $this->fValid=true;
        return true;
    } else {
        $this->fValid=false;
        return $aErrs;
    }
  }
    
    
    
    
    public function isValid()
    {
        if (isset($this->fValid)) {
            return $this->fValid;
        }
        $this->fValid=false;
        if ($this->hasData()) {
            $this->validate();
        }
        return $this->fValid;
    }
    
    
    
    
    public function getErrors()
    {
        if ($this->fValid===null) {
            $this->validate();
        }
        return $this->aErrors;
    }
    
    
    
    
    public function isPost()
    {
        if ($this->fPost===null) {
            $this->getFromPost();
        }
        return $this->fPost;
    }
    



    /**
     * Lit les données, les valide et les écrit dans la bdd si elles sont ok
     *
     * @return boolean true si formulaire valide, false si non valide ou null si aucune données
     */
    public function process()
    {
        if ($this->load()) {
            if ($this->validate()===true && $this->putInDb()===true) {
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
        if (!$this->fLoaded) {
            $getFromDb=$this->getFromDb();
            $getFromPost=$this->getFromPost();
            if ($getFromDb || $getFromPost ) {
                $this->fHasData=true;
            } else {
                $this->fHasData=false;
            }
            $this->fLoaded=true;
        }
        return $this->fHasData;
    }

    
    
    
    public function isLoaded()
    {
        return $this->fLoaded;
    }
    public function hasData()
    {
        return $this->fHasData;
    }
    


}
