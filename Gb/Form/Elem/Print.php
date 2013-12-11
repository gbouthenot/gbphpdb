<?php
/**
 * Gb_Form_Elem_Print
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


class Gb_Form_Elem_Print extends Gb_Form_Elem_Abstract
{
    public function __construct($name, $text)
    {
        $availableParams=array();
        $aParams=array("javascriptEnabled"=>false, "value"=>$text, "backendCol"=>false);
        return parent::__construct($name, $availableParams, $aParams);
    }

    public function getInput($value, $inInput, $inputJs)
    {
        $value;$inInput;$inputJs;
        return $value;
    }

    // post value interdit sur cet élément
    public function rawValue($text=null)
    {
        $text;
        return $this->value();
    }
}
