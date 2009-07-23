<?php

/**
 * Gb_Form_Elem_Select
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Selectmultiple extends Gb_Form_Elem
{
    protected $_args;
    
    
    public function getInput($value, $inInput, $inputJs)
    {
        $value;
        $aValues=$this->args();
        $value=parent::value();
        $elemid=$this->elemId();
        $ret="";
        $ret.="<select multiple='multiple' id='{$elemid}' name='{$elemid}[]' class='multiple' $inInput $inputJs>\n";
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
              if (in_array($ordre,$value))
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
    

    
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("args");
        $aParams=array_merge(array("errorMsgMissing"=>"Veuillez faire un choix", "value"=>array()), $aParams);
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    

    protected function getInputJavascript()
    {
        $elemid=$this->elemId();
        return "onchange='javascript:validate_{$elemid}();' onkeyup='javascript:validate_{$elemid}();'";
    }
    public function renderJavascript()
    {
        if (!$this->javascriptEnabled()) {
            return "";
        }
        
        $ret="";
        $elemid=$this->elemId();
        
        // par défaut, met en classOK, si erreur, repasse en classNOK
        $ret.=" \$('{$elemid}_div').className='OK';\n";
        // enlève le message d'erreur
        $ret.=" var e=\$('{$elemid}_div').select('div[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        $ret.=" var e=\$('{$elemid}_div').select('span[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        
        // attention utilise prototype String.strip()
        $ret.="var value=\$F('{$elemid}');\n";
        $ret.="console.log(value);\n";
        
        // traitement fMandatory
        if ($this->fMandatory()) {
            $ret.="if (value.length==0) {";
            $ret.=" \$('{$elemid}_div').className='NOK';\n";
            $ret.="}\n";
        }

        $notValues=$this->notValue();
        if (count($notValues)) {
            foreach ($notValues as $notValue) {
                 $ret.=" var bornevalue=value;\n";
                 if (strpos($notValue, "GBFORM_")===0) {
                     /** @ TODO */
                    /*
                        // borne commence par GBFORM_
                       $notValue="\$F('$notValue')";
                       $ret.=" var notvalue={$notValue};\n";
                    */
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
    



    /**
     * Valide le formulaire
     * En cas d'erreur, $this->setErrorMsg pour chaque $nom incorrect
     *
     * @return array("nom" => "erreur") ou true si aucune erreur (attention utiliser ===)
     */
    public function validate(Gb_Form2 $form)
    {
        $form;
        $values=$this->value();
        $fMandatory=$this->fMandatory();
        $args=$this->args();

        // valeur non transmise
        if (count($values)==0 && $fMandatory) {
            return $this->errorMsgMissing();
        }

        
        // valeur transmise n'est pas dans la liste
        foreach($values as $value) {
            $found=0;
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
        }
        
        $value=$values;
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
     * @param array[optional] $text
     * @return Gb_Form_Elem_Selectmultiple|String 
     */
    public function value($text=null)
    {   
        $args=$this->args();
        $vals=array();
        if ($text===null) {
            foreach($this->rawValue() as $value) { 
                if (isset($args[$value])) {
                    $value=$args[$value];
                    if (is_array($value)) { $value=$value[0]; }
                    $vals[]=$value;
                }
            }
            return $vals;
        } else {
            if (is_array($text)) {
                foreach ($args as $ordre=>$val) {
                    $thisval=is_array($val)?$val[0]:$val;
                    if (in_array($thisval, $text)) {
                        $vals[]=$ordre;
                    }
                }
            }
            $this->rawValue($vals);
            return $this;
        }
    }

    public function rawValue($text=null)
    {
        return parent::value($text);
    }
    

}