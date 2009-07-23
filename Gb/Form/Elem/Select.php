<?php

/**
 * Gb_Form_Elem_Select
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Select extends Gb_Form_Elem
{
    protected $_args;
    
    
    public function getInput($value, $inInput, $inputJs)
    {
        $value();
        $aValues=$this->args();
        $value=parent::value();
        $elemid=$this->elemId();
        $ret="";
        $ret.="<select id='{$elemid}' name='{$elemid}' class='simple' $inInput $inputJs>\n";
        $num=0;
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
        return $ret;
    }
    
    public function renderJavascript()
    {
        if (!$this->javascriptEnabled()) {
            return "";
        }
        
        $args=$this->args();
        $ret="";
        $elemid=$this->elemId();
        
        // par d�faut, met en classOK, si erreur, repasse en classNOK
        $ret.=" \$('{$elemid}_div').className='OK';\n";
        // enl�ve le message d'erreur
        $ret.=" var e=\$('{$elemid}_div').select('div[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        $ret.=" var e=\$('{$elemid}_div').select('span[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        
        // attention utilise prototype String.strip()
        $ret.="var value=\$F('{$elemid}');\n";
        
        // traitement fMandatory
        if ($this->fMandatory()) {
            $aValues="";
            foreach($args as $ordre=>$val) {
                $val=htmlspecialchars($val[0], ENT_QUOTES);
                if ($val===false) { $val="false"; }
                $aValues[]="'$ordre':'$val'";
            }
            $ret.="var {$elemid}_values = { ".implode(", ",$aValues)."};\n";
            $ret.="if (({$elemid}_values[value])=='false') {\n";
            $ret.=" \$('{$elemid}_div').className='NOK';\n";
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
                 $ret.=" if (bornevalue == notvalue) {";
                 $ret.=" \$('{$elemid}_div').className='NOK';";
                 $ret.="}\n";
            }
        }

        $ret2="";
        if (strlen($ret)) {
            $ret2="function validate_{$elemid}()\n";
            $ret2.="{\n";
            $ret2.=$ret;
            $ret2.="}\n";
        }
        return $ret2;
    }

    protected function getInputJavascript()
    {
        $elemid=$this->elemId();
        return "onchange='javascript:validate_{$elemid}();' onkeyup='javascript:validate_{$elemid}();'";
    }
    
    
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("args", "notValue");
        $aParams=array_merge(array("errorMsgMissing"=>"Veuillez faire un choix"), $aParams);
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    





    /**
     * Valide le formulaire
     * En cas d'erreur, $this->setErrorMsg pour chaque $nom incorrect
     *
     * @return array("nom" => "erreur") ou true si aucune erreur (attention utiliser ===)
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
        
        if ($value==='false' && $fMandatory) {
            return $this->errorMsgMissing();        
        }
        
        $chk=$this->_maxminvalueValidate("=", $value, $notValues, $form); if (strlen($chk)) { return $chk; }
        
        $validateFunc=$this->validateFunc();
        if (strlen($validateFunc)  && strlen($value)) {
            // 1er argument: fonction � appeler
            $callback=$validateFunc[0];
            // 2eme: �ventuel parametres
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