<?php

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
    
    
    public function getInput($nom, $value, $inInput, $inputJs)
    {
        $aValues=$this->args();
        $value=$this->value();
        if ($value) {
            $value="checked='checked'";
        } else {
            $value="";
        }
        $ret="";
        $ret.="<input type='checkbox' $value id='GBFORM_$nom' name='GBFORM_$nom' $inInput $inputJs />";
        return $ret;
    }
    
    public function renderJavascript()
    {
        if (!$this->javascriptEnabled()) {
            return "";
        }
        
        $args=$this->args();
        $ret="";
        $nom=$this->name();
        
        // par défaut, met en classOK, si erreur, repasse en classNOK
        $ret.=" \$('GBFORM_{$nom}_div').className='OK';\n";
        // enlève le message d'erreur
        $ret.=" var e=\$('GBFORM_{$nom}_div').select('div[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        $ret.=" var e=\$('GBFORM_{$nom}_div').select('span[class=\"ERROR\"]').first(); if (e!=undefined){e.innerHTML='';}\n";
        
        $ret.="var value=\$F('GBFORM_$nom');\n";
        
        // traitement fMandatory
        if ($this->fMandatory()) {
              $ret.="if (value!='true' && value!='on') {\n";
              $ret.=" \$('GBFORM_{$nom}_div').className='NOK';\n";
              $ret.="}\n";
        }

        $ret2="";
        if (strlen($ret)) {
            $ret2="function validate_GBFORM_{$nom}()\n";
            $ret2.="{\n";
            $ret2.=$ret;
            $ret2.="}\n";
        }
        return $ret2;
    }

    protected function getInputJavascript($nom)
    {
        return "onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();'";
    }
    
    
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("args", "notValue");
        $aParams=array_merge(array("errorMsgMissing"=>"Veuillez faire un choix", "value"=>false), $aParams);
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
    

    
    
}