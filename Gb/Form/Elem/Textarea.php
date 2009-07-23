<?php

/**
 * Gb_Form_Elem_Textarea
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Textarea extends Gb_Form_Elem
{
    
    public function getInput($value, $inInput, $inputJs)
    {
        $elemid=$this->elemId();
        return "<textarea id='{$elemid}' name='{$elemid}' $inInput $inputJs>".htmlspecialchars($value)."</textarea>";
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
        $ret.="var value=remove_accents(\$F('{$elemid}').strip());\n";

        // traitement fMandatory
        if ($this->fMandatory()) {
          $ret.="if (value=='') {\n";
          $ret.=" \$('{$elemid}_div').className='NOK';\n";
          $ret.="}\n";
        }

        // traitement regexp
        $regexp=$this->regexp();
        if ($regexp!==null) {
            if (isset(self::$_commonRegex[$regexp])) {
                //regexp connu: remplace par le contenu
                $regexp=self::$_commonRegex[$regexp];
            }
            $ret.="var regexp=$regexp\n";
            $ret.="if (!regexp.test(value)) {\n";
            $ret.=" \$('{$elemid}_div').className='NOK';\n";
            $ret.="}\n";
        }
        
        if (!$this->fMandatory()) {
          $ret.="if (value=='') {\n";
          $ret.=" \$('{$elemid}_div').className='OK';\n";
          $ret.="}\n";
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
        $availableParams=array("regexp");
        $aParams=array_merge(array("errorMsgMissing"=>"Texte non rempli"), $aParams);
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
        $value=strtolower(Gb_String::mystrtoupper(trim($this->value())));
        $regexp=$this->regexp();
        $fMandatory=$this->fMandatory();

        if (strlen($value) && strlen($regexp)) {
            if (!preg_match($regexp, $value)) {
                return "Valeur incorrecte";
            }
        }

        if ($fMandatory && strlen($value)==0) {
            return $this->errorMsgMissing();
        }

        $validateFunc=$this->validateFunc();
        if (is_array($validateFunc)  && strlen($value)) {
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
    
}
