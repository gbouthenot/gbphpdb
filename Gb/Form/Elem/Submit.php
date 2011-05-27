<?php
/**
 * Gb_Form_Elem_Submit
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


class Gb_Form_Elem_Submit extends Gb_Form_Elem_Abstract
{
    protected $_onclick;
    
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("onclick");
        $aParams["javascriptEnabled"]=false;
        $aParams["backendCol"]=false;
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    public function getInput($value, $inInput, $inputJs)
    {
        $elemid=$this->elemId();
        $value=htmlspecialchars($value);
        if (strlen($value)) { $value="value='$value'"; }

        $onclick=$this->onclick();
        if (strlen($onclick)) { $onclick="onclick='".htmlspecialchars($onclick, ENT_QUOTES)."'"; }
        
        return "<input type='submit' class='submit' name='{$elemid}' $value $inInput $inputJs $onclick />";
    }
    

    
    /**
     * get/set onclick
     * @param string[optional] $text
     * @return Gb_Form_Elem_Submit|String 
     */
    public final function onclick($text=null)
    {   
        if ($text===null) {         return $this->_onclick; }
        else { $this->_onclick=$text; return $this;}
    }
    
}
