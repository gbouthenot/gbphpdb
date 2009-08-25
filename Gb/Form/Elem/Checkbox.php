<?php

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

/**
 * Gb_Form_Elem_Select
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Checkbox extends Gb_Form_Elem
{
    protected $_args;
    
    
    public function getInput($value, $inInput, $inputJs)
    {
        if ($value) {
            $value="checked='checked'";
        } else {
            $value="";
        }
        $ret="";
        $elemid=$this->elemId();
        $ret.="<input type='checkbox' $value id='$elemid' name='$elemid' $inInput $inputJs />";
        return $ret;
    }
    
    public function renderJavascript()
    {
        if (!$this->javascriptEnabled() || !$this->fMandatory()) {
            return "";
        }
        
        $ret="";
        $elemid=$this->elemId();
        
        // par défaut, met en classOK, si erreur, repasse en classNOK
        $ret.=" \$('{$elemid}_div').className='OK';\n";
        // enlève le message d'erreur
        $ret.=" var e=\$('{$elemid}_div').select('div[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        $ret.=" var e=\$('{$elemid}_div').select('span[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        
        $ret.="var value=\$F('$elemid');\n";
        
        // traitement fMandatory
        if ($this->fMandatory()) {
              $ret.="if (value!='true' && value!='on') {\n";
              $ret.=" \$('{$elemid}_div').className='NOK';\n";
              $ret.="}\n";
        }

        $ret2="";
        if (strlen($ret)) {
            $ret2="function validate_$elemid()\n";
            $ret2.="{\n";
            $ret2.=$ret;
            $ret2.="}\n";
        }
        return $ret2;
    }

    protected function getInputJavascript()
    {
        $elemid=$this->elemId();
        if ($this->fMandatory()) {
            return "onchange='javascript:validate_$elemid();' onkeyup='javascript:validate_$elemid();'";
        } else {
            return "";
        }
    }
    
    
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("args", "notValue");
        $aParams=array_merge(array("errorMsgMissing"=>"Cette case doit Ãªtre cochÃ©e", "value"=>false), $aParams);
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
        $form;
        $value=$this->value();
        $fMandatory=$this->fMandatory();

        // valeur non transmise
        if ($value==false) {
            if ($fMandatory) {
                return $this->errorMsgMissing();
            } else {
                return true;
            }
        }

        $validateFunc=$this->validateFunc();
        if (strlen($validateFunc)) {
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
     * @param string[optional] $text
     * @return Gb_Form_Elem|String 
     */
    public function value($text=null)
    {
        if ($text!==null) {
            if ($text==="on" || $text==="true" || $text==="1" || $text===1 || $text===true) {$text=1;} else {$text=0;}
        }
        return parent::value($text);
    }
    

    
    
}