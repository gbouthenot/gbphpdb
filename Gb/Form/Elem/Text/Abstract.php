<?php
/**
 * Gb_Form_Elem_Text_Abstract
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}


abstract class Gb_Form_Elem_Text_Abstract extends Gb_Form_Elem_Abstract
{
    
    
    
    public function getInput($value, $inInput, $inputJs)
    {
        $sValue=htmlspecialchars($value);
        $htmlInInput=$this->getHtmlInInput();
        $elemid=$this->elemId();
        return "<input $htmlInInput id='{$elemid}' name='{$elemid}' value='$sValue' $inInput $inputJs />";
    }
    
    abstract protected function getHtmlInInput();


    protected function _renderJavascript($js=null)
    {
        $js = null;
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
        
        $ret.=$this->_maxminvalueJS($this->minValue(), "<",  true,  $regexp);    // traitement minvalue
        $ret.=$this->_maxminvalueJS($this->maxValue(), ">",  true,  $regexp);    // traitement maxvalue
        $ret.=$this->_maxminvalueJS($this->notValue(), "==", false, $regexp);    // traitement notvalue
        
        if (!$this->fMandatory()) {
          $ret.="if (value=='') {\n";
          $ret.=" \$('{$elemid}_div').className='OK';\n";
          $ret.="}\n";
        }

        $ret2=parent::_renderJavascript($ret);
        return $ret2;
    }

    protected function getInputJavascript()
    {
        $elemid=$this->elemId();
        return "onchange='javascript:validate_{$elemid}();' onkeyup='javascript:validate_{$elemid}();'";
    }
    
    
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("minValue", "maxValue", "regexp", "notValue");
        $aParams=array_merge(array("errorMsgMissing"=>"Champ non rempli"), $aParams);
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    
    private function _maxminvalueJS($aValues, $operator, $fEval, $regexp)
    {
        $ret="";
        $elemid=$this->elemId();
        
        // traitement minvalue/maxvalue
        if (count($aValues)) {
            foreach ($aValues as $borne) {
                if (is_array($borne) && $regexp!==null) {
                    // si array, alors extrait la valeur du regexp avant de comparer
                    $ret.=" var bornevalue=value.replace($regexp, \"{$borne[0]}\");\n";
                    $borne=$borne[1];
                } else {
                    $ret.=" var bornevalue=value;\n";
                }
                if (strpos($borne, "GBFORM_")===0) {
                    // borne commence par GBFORM_
                    $borne="\$F('$borne')";
                } else {
                    $borne='"'.addslashes($borne).'"';
                }
                if (0 && $fEval) {
                    $ret.=" var borne=eval({$borne});\n";
                } else {
                    $ret.=" var borne={$borne};\n";
                }
                $ret.=" if (bornevalue $operator borne) {";
                $ret.=" \$('{$elemid}_div').className='NOK';";
                $ret.="}\n";
            }
        }
        return $ret;
    }
    






















    /**
     * Valide l'élément
     * En cas d'erreur, $this->setErrorMsg pour chaque $nom incorrect
     *
     * @return string si erreur ou true si aucune erreur (attention utiliser ===)
     */
    public function validate(Gb_Form2 $form)
    {
        $value=strtolower(Gb_String::mystrtoupper(trim($this->value())));
        $regexp=$this->regexp();
        $minValue=$this->minValue();
        $maxValue=$this->maxValue();
        $notValue=$this->notValue();
        $fMandatory=$this->fMandatory();

        if (strlen($value) && strlen($regexp)) {
            if (!preg_match($regexp, $value)) {
                return "Valeur incorrecte";
            }
        }

        $chk=$this->_maxminvalueValidate("<", $value, $minValue, $form, $regexp); if (strlen($chk)) { return $chk; }
        $chk=$this->_maxminvalueValidate(">", $value, $maxValue, $form, $regexp); if (strlen($chk)) { return $chk; }
        $chk=$this->_maxminvalueValidate("=", $value, $notValue, $form, $regexp); if (strlen($chk)) { return $chk; }

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

    private function _maxminvalueValidate($op, $value, $aBornes, Gb_Form2 $form, $regexp)
    {
        if (strlen($value) && count($aBornes)) {
            foreach ($aBornes as $borne) {
                $bornevalue=$value;
                if (is_array($borne) && strlen($regexp)) {
                    // si array, alors extrait la valeur du regexp avant de comparer
                    $bornevalue=preg_replace( $regexp, $borne[0], $value);
                    $borne=$borne[1];
                }
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
    












}