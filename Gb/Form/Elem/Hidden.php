<?php

/**
 * Gb_Form_Elem_Text_Hidden
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Hidden extends Gb_Form_Elem
{
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array();
        $aParams=array_merge(array("javascriptEnabled"=>false, "container"=>""), $aParams);
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    public function getInput($nom, $value, $inInput, $inputJs)
    {
        $sValue=htmlspecialchars($value, ENT_QUOTES);
        return "<input type='hidden' id='GBFORM_$nom' name='GBFORM_$nom' value='$sValue' $inInput $inputJs />";
    }

}