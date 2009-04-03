<?php

/**
 * Gb_Form_Elem_Text_Hidden
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Print extends Gb_Form_Elem
{
    public function __construct($name, $text)
    {
        $availableParams=array();
        $aParams=array("javascriptEnabled"=>false, "container"=>"", "value"=>$text, "backendCol"=>false);
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    public function getInput($nom, $value, $inInput, $inputJs)
    {
        return $value;
    }

    // post value interdit sur cet élément
    public function rawValue($text=null)
    {
        return $this->value();
    }
}