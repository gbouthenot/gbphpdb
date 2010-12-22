<?php

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

/**
 * Gb_Form_Elem_Text_Hidden
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Print extends Gb_Form_Elem_Abstract
{
    public function __construct($name, $text)
    {
        $availableParams=array();
        $aParams=array("javascriptEnabled"=>false, "container"=>"", "value"=>$text, "backendCol"=>false);
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    public function getInput($value, $inInput, $inputJs)
    {
        $value;$inInput;$inputJs;
        return $value;
    }

    protected function _renderJavascript()
    {
        return "";
    }
        
    // post value interdit sur cet élément
    public function rawValue($text=null)
    {
        $text;
        return $this->value();
    }
}