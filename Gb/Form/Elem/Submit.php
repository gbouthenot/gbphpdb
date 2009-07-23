<?php

class Gb_Form_Elem_Submit extends Gb_Form_Elem
{
    protected $_onclick;
    
    public function __construct($name, array $aParams=array())
    {
        $availableParams=array("onclick");
        $aParams["javascriptEnabled"]=false;
        return parent::__construct($name, $availableParams, $aParams);
    }
    
    public function getInput($value, $inInput, $inputJs)
    {
        $elemid=$this->elemId();
        $value=htmlspecialchars($value);
        if (strlen($value)) { $value="value='$value'"; }
        return "<input type='submit' class='submit' name='{$elemid}' $value $inInput $inputJs />";
    }
    
    public function getInputJavascript()
    {
        $onclick=$this->onclick();
        if (strlen($onclick)) { $onclick="onclick='$onclick'"; }
        return $onclick;
    }

    /**
     * get/set onclick
     * @param string[optional] $text
     * @return Gb_Form_Elem_Text_Abstract|String 
     */
    public final function onclick($text=null)
    {   
        if ($text===null) {         return $this->_onclick; }
        else { $this->_onclick=$text; return $this;}
    }
    
}
