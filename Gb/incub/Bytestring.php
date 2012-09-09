<?php

class Bytestring
{

    protected $_str;

    /**
     * Constructor
     * @param string[optional] $chars initial content
     */
    public function __construct($chars=null)
    {
        $this->_str = "";
        if (is_string($chars)) {
            $this->str = $chars;
        }
    }
    

    
    /**
     * Append one value
     * @param integer|array $values
     * @param integer $width number of bytes
     * @return Bytestring
     * @throws Gb_Exception
     */
    public function appendIntsMsb($values, $width=1)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        
        foreach ($values as $value) {
            if ($width>4) {
                throw new Gb_Exception("unhandled width");
            }
            if ($width>=4) {
                $this->_appendByte(($value>>24)&0xff);
            }
            if ($width>=3) {
                $this->_appendByte(($value>>16)&0xff);
            }
            if ($width>=2) {
                $this->_appendByte(($value>>8)&0xff);
            }
            if ($width>=1) {
                $this->_appendByte($value&0xff);
            }
        }
        return $this;
    }

    
    /**
     * Append one value
     * @param integer|array $value
     * @param integer $width number of bytes
     * @return Bytestring
     * @throws Gb_Exception
     */
    public function appendIntsLsb($values, $width=1)
    {
        if ($width>4) {
            throw new Gb_Exception("unhandled width");
        }

        if (!is_array($values)) {
            $values = array($values);
        }
        
        foreach ($values as $value) {
            if ($width>=1) {
                $this->_appendByte($value&0xff);
            }
            if ($width>=2) {
                $this->_appendByte(($value>>8)&0xff);
            }
            if ($width>=3) {
                $this->_appendByte(($value>>16)&0xff);
            }
            if ($width>=4) {
                $this->_appendByte(($value>>24)&0xff);
            }
        }
        return $this;
    }
    
    
    
    /**
     * Append byte (values)
     * @param integer|array $values
     * @return Bytestring
     */
    public function appendByte($values)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        
        foreach ($values as $value) {
            $this->_str .= chr($value);
        }
        return $this;
    }

    /**
     * Append strings
     * @param string|array $chars
     * @return Bytestring
     */
    public function appendStrings($values)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        foreach ($values as $value) {
            for ($i=0,$l=strlen($value); $i<$l; $i++) {
                $this->_str .= substr($value, $i, 0);
            }
        }
        return $this;
    }
    
    
    /**
     * Returns a binary string
     */
    public function __toString()
    {
        return $this->_str;
    }

    
    
    
}
