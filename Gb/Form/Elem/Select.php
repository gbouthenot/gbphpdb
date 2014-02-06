<?php
/**
 * Gb_Form_Elem_Select
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

require_once(_GB_PATH."Form/Elem/Abstract.php");


class Gb_Form_Elem_Select extends Gb_Form_Elem_Abstract
{
    protected $_args;


    public function getInput($value, $inInput, $inputJs)
    {
        $value;
        $aValues  = $this->args();
        $rawvalue = $this->rawvalue();
        $elemid   = $this->elemId();
        $classInput = $this->classInput();
        $ret="";
        $ret.="<select id='{$elemid}' name='{$elemid}' class='simple $classInput' $inInput $inputJs>\n";
        $fOptgroup=false;
        foreach ($aValues as $ordre=>$aOption){
          $sVal=htmlspecialchars(is_array($aOption)?$aOption[0]:$aOption, ENT_QUOTES);
          $sLib=htmlspecialchars(is_array($aOption)?(isset($aOption[1])?$aOption[1]:$aOption[0]):$aOption, ENT_QUOTES);
          if ($sVal=="optgroup") {
              if ($fOptgroup) {
                  $ret.="</optgroup>\n";
              }
              $ret.="<optgroup label='$sLib'>\n";
              $fOptgroup=true;
          } else {
              $sSelected="";
              if ( (strlen($rawvalue)) && ($ordre == $rawvalue) )
                $sSelected="selected='selected'";
              $ret.="<option value='$ordre' $sSelected>$sLib</option>\n";
          }
        }
        if ($fOptgroup) {
            $ret.="</optgroup>\n";
        }
        $ret.="</select>\n";
        return $ret;
    }

    protected function _renderJavascript($js=null)
    {
        $js = null;
        $args=$this->args();
        $ret="";
        $elemid=$this->elemId();
        $classContainer = $this->classContainer();

        // par défaut, met en classOK, si erreur, repasse en classNOK
        $ret .= "gbSetClass('{$elemid}_div', 'OK $classContainer');\n";

        // enlève le message d'erreur
        $ret .= "gbRemoveError('{$elemid}');\n";

        $ret .= "var value=\$F('{$elemid}');\n";

        $aValues="";
        foreach($args as $ordre=>$val) {
            if (is_array($val)) {
                $val = $val[0];
            }
            if ($val===false) {
                $val="false";
            } else {
                $val = htmlspecialchars($val, ENT_QUOTES);
            }
            $aValues[]="'$ordre':'$val'";
        }
        $ret.="var {$elemid}_values = { ".implode(", ",$aValues)."};\n";

            // traitement fMandatory
        if ($this->fMandatory()) {
            $ret.="if (({$elemid}_values[value])=='false') {\n";
            $ret.=" gbSetClass('{$elemid}_div', 'NOK $classContainer');\n";
            $ret.="}\n";
        }

        $notValues=$this->notValue();
        if (count($notValues)) {
            foreach ($notValues as $notValue) {
                 $ret.=" var bornevalue=value;\n";
                 if (strpos($notValue, "GBFORM_")===0) {
                    // borne commence par GBFORM_
                   $notValue="\$F('$notValue')";
                   $ret.=" var notvalue={$notValue};\n";
                 } else {
                   $ret.=" var notvalue=\"".addslashes($notValue)."\";\n";
                 }
                 $ret.=" if ((bornevalue == notvalue) && ({$elemid}_values[value] != 'false')) {";
                 $ret.=" gbSetClass('{$elemid}_div', 'NOK $classContainer');\n";
                 $ret.="}\n";
            }
        }

        $ret2=parent::_renderJavascript($ret);
        return $ret2;
    }

    protected function getInputJavascript()
    {
        $elemid=$this->elemId();
        return "onchange='javascript:validate_{$elemid}();' onkeyup='javascript:validate_{$elemid}();'";
    }

    /*
     * Exemple d'appel: $form->append(new Gb_Form_Elem_Select( "CHOIXFORM", array("backendCol"=>"aca_choixform","fMandatory"=>true,
                                                                      "args"=>array(array('false',"choisissez"), "PE", "CPE"))    ))

     */
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("args", "notValue");
        $aParams=array_merge(array("errorMsgMissing"=>"Veuillez faire un choix"), $aParams);
        return parent::__construct($name, $availableParams, $aParams);
    }







    /**
     * Valide l'élément
     * En cas d'erreur, $this->setErrorMsg pour chaque $nom incorrect
     *
     * @return string si erreur ou true si aucune erreur (attention utiliser ===)
     */
    public function validate(Gb_Form2 $form)
    {
        $value=$this->value();
        $notValues=$this->notValue();
        $fMandatory=$this->fMandatory();
        $args=$this->args();

        // valeur non transmise
        if (strlen($value)==0) {
            if ($fMandatory) {
                return $this->errorMsgMissing();
            } else {
                return true;
            }
        }

        // valeur transmise n'est pas dans la liste
        $found=false;
        foreach ($args as $val) {
            $thisval=is_array($val)?$val[0]:$val;
            if ($value===$thisval) {
                $found=1;
                break;
            }
        }

        if (!$found) {
            return "Choix invalide";
        }

        if ($value==='false') {
            // no value selected. if mandatory: ERROR, else OK.
            if ($fMandatory) {
                return $this->errorMsgMissing();
            } else {
                return true;
            }
        }

        $chk=$this->_maxminvalueValidate("=", $value, $notValues, $form); if (strlen($chk)) { return $chk; }

        $validateFunc=$this->validateFunc();
        if (strlen($validateFunc)  && strlen($value)) {
            // 1er argument: fonction à appeler
            $callback=$validateFunc[0];
            // 2eme: éventuel parametres
            $params=array();
            if (isset($validateFunc[1])) {
              $params=$validateFunc[1];
            }
            $params=array_merge(array($value), $params);
            $ret=call_user_func_array($callback, $params);
            if ($ret===false) {
                return "Choix invalide";
            } elseif (is_string($ret)) {
                return $ret;
            }
        }

        return true;
    }


    private function _maxminvalueValidate($op, $value, $aBornes, Gb_Form2 $form)
    {
        if (strlen($value) && count($aBornes)) {
            foreach ($aBornes as $borne) {
                $bornevalue=$value;
                $sBorne=$borne;
                if (strpos($borne, "GBFORM_")===0) {
                    // borne commence par GBFORM_
                    $sBorne=substr($borne, 7);
                    $elem=$form->getElem($sBorne);
                    $borne=$elem->value();
                    $sBorne=$elem->publicName();
                    $sBorne.=" ($borne)";
                }
                if ($op=="<") {
                    if ($bornevalue < $borne) {
                        return "Doit etre superieur ou egal a $sBorne";
                    }
                } elseif ($op==">") {
                    if ($bornevalue > $borne) {
                        return "Doit etre plus petit ou egal a $sBorne";
                    }
                } elseif ($op=="=") {
                    if ($bornevalue == $borne) {
                        return "Doit etre different de $sBorne";
                    }
                }
            }
        }
        return "";
    }








    /**
     * get/set args
     * @param array[optional] $text
     * @return Gb_Form_Elem_Select|String
     */
    public function args(array $text=null)
    {
        if ($text===null) {         return $this->_args; }
        else { $this->_args=$text; return $this;}
    }




    /**
     * get/set value
     * @param string[optional] $text
     * @return Gb_Form_Elem_Select|String
     */
    public function value($text=null)
    {
        $args=$this->args();
        if ($text===null) {
            $value=$this->rawValue();
            if (isset($args[$value])) {
                $value=$args[$value];
                if (is_array($value)) { $value=$value[0]; }
            }
            return $value;
        } else {
            foreach ($args as $ordre=>$val) {
                $thisval=is_array($val)?$val[0]:$val;
                if ($thisval===$text) {
                    $text=$ordre;
                    break;
                }
            }
            $this->rawValue($text);
            return $this;
        }
    }

    public function rawValue($text=null)
    {
        return parent::value($text);
    }

}
