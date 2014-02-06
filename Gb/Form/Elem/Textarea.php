<?php
/**
 * Gb_Form_Elem_Textarea
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

require_once(_GB_PATH."String.php");
require_once(_GB_PATH."Form/Elem/Abstract.php");


class Gb_Form_Elem_Textarea extends Gb_Form_Elem_Abstract
{

    public function getInput($value, $inInput, $inputJs)
    {
        $elemid=$this->elemId();
        $classInput = $this->classInput();
        return "<textarea id='{$elemid}' name='{$elemid}' $inInput $inputJs class='$classInput'>".htmlspecialchars($value)."</textarea>";
    }


    protected function _renderJavascript($js=null)
    {
        $js = null;
        $ret="";
        $elemid=$this->elemId();
        $classContainer = $this->classContainer();

        // par défaut, met en classOK, si erreur, repasse en classNOK
        $ret .= "gbSetClass('{$elemid}_div', 'OK $classContainer');\n";

        // enlève le message d'erreur
        $ret .= "gbRemoveError('{$elemid}');\n";

        $ret .= "var value=remove_accents(gbtrim(\$F('{$elemid}')));\n";
        // traitement fMandatory
        if ($this->fMandatory()) {
          $ret.="if (value=='') {\n";
          $ret.=" gbSetClass('{$elemid}_div', 'NOK $classContainer');\n";
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
            $ret.=" gbSetClass('{$elemid}_div', 'NOK $classContainer');\n";
            $ret.="}\n";
        }

        if (!$this->fMandatory()) {
          $ret.="if (value=='') {\n";
          $ret.=" gbSetClass('{$elemid}_div', 'OK $classContainer');\n";
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
        $availableParams=array("regexp");
        $aParams=array_merge(array("errorMsgMissing"=>"Texte non rempli"), $aParams);
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
