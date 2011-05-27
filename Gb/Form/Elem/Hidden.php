<?php
/**
 * Gb_Form_Elem_Hidden
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


class Gb_Form_Elem_Hidden extends Gb_Form_Elem_Abstract
{
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array();
        $aParams=array_merge(array("javascriptEnabled"=>false, "container"=>""), $aParams);
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    public function getInput($value, $inInput, $inputJs)
    {
        $sValue=htmlspecialchars($value, ENT_QUOTES);
        $elemid=$this->elemId();
        return "<input type='hidden' id='{$elemid}' name='{$elemid}' value='$sValue' $inInput $inputJs />";
    }

}