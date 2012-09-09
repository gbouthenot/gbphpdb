<?php

class Bitstring
{

    protected $_str;
    protected $_curByte;
    protected $_curWeight;

    // for endianness:
    protected $_typeMsb;
    protected $_bitWeight_first;
    protected $_bitWeight_empty;
    
    const _zBase32_alphabet = "ybndrfg8ejkmcpqxot1uwisza345h769";
    
    /**
     * Constructor
     * @param string $type "MSB1" or "LSB1"
     */
    public function __construct($type)
    {
        if ("MSB1"==$type) {
            $this->_bitWeight_first = 128;
            $this->_bitWeight_empty = 0;
            $this->_typeMsb = true;
        } elseif ("LSB1"==$type) {
            $this->_bitWeight_first = 1;
            $this->_bitWeight_empty = 256;
            $this->_typeMsb = false;
        }
        $this->_str = "";
        $this->_curByte = 0;
        $this->_curWeight = $this->_bitWeight_first;
    }

    
    /**
     * Append one bit
     * @param boolean $bit
     */
    public function appendBit($bit)
    {
        $curWeight = $this->_curWeight;
        if ($bit) {
            $this->_curByte |= $curWeight;
        }
        
        if ($this->_typeMsb) {
            $curWeight >>= 1;
        } else {
            $curWeight <<= 1;
        }
        if ($curWeight == $this->_bitWeight_empty) {
            $curWeight = $this->_bitWeight_first;
            $this->_str .= chr($this->_curByte);
            $this->_curByte = 0;
        }
        
        $this->_curWeight = $curWeight;
    }
    
    
    /**
     * Append one (or more) values
     * @param integer|array $values
     * @param integer $width number of bits
     */
    public function appendValues($values, $width)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        
        foreach ($values as $value) {
            $this->appendValue($value, $width);
        }
    }

    /**
     * Append one value
     * @param integer $value
     * @param integer $width number of bits
     */
    public function appendValue($value, $width)
    {
        // compute highest bit weight
        $weight = 1;
        
        if ($this->_typeMsb) {
            // Msb
            if ($width > 1) {
                for ($i=1; $i<$width; $i++) {
                    $weight <<= 1;
                }
            }
        }
                    
        for ($i=0; $i<$width; $i++) {
            $bit = 0;
            if ($value & $weight) {
                $bit = 1;
            }
            $this->appendBit($bit);
            if ($this->_typeMsb) {
                $weight >>= 1;
            } else {
                $weight <<= 1;
            }
        }
    }
    
    
    
    /**
     * Append bits from a "010110" string
     * @param string $bits
     */
    public function appendBits($bits)
    {
        for ($c=strlen($bits),$i=0; $i<$c; $i++) {
            $bit = $bits[$i];
            if (("0"==$bit) || ("1"==$bit) ) {
                $this->appendBit($bits[$i]);
            }
        }
    }
    
    
    /**
     * Returns a binary string
     * @return string
     */
    public function __toString()
    {
        $last = "";
        if ($this->_curWeight != $this->_bitWeight_first) {
            $last = chr($this->_curByte);
        }
        return $this->_str . $last;
    }


    /**
     * Returns the number of accumulated bits
     * @return integer
     */
    public function getLength()
    {
        $curWeight = $this->_curWeight;
        $curBits   = 0;

        if ($this->_typeMsb) {
            // MSB
            for($i=0; $i<8; $i++) {
                if ($this->_bitWeight_first == $curWeight) {
                    break;
                }
                $curWeight <<= 1;
                $curBits++;
            }
        } else {
            // LSB
            for($i=0; $i<8; $i++) {
                if ($this->_bitWeight_first == $curWeight) {
                    break;
                }
                $curWeight >>= 1;
                $curBits++;
            }
        }
        
        return (strlen($this->_str)<<3)+$curBits;
    }
    


    /**
     * Shift the data one bit left. Returns the lost MSB
     * @return integer bit or null of no bits shifted
     * NOT TESTED AT ALL !!!
     */
    public function shiftLeft()
    {
        $lenBytes = strlen($this->str);
        $lostBit = null;
        $tmpLost = null;
        $tmpByte = null;
        
        if ($this->_typeMsb) {
            // MSB
            if ($lenBytes) {
                $lostBit = (ord($this->_str[0])&0x80) ? (1) : (0);

                $tmpLost = ($this->curByte & $this->curWeight)?(1):(0);
                if ($this->curWeight!=0x80) {
                    $this->curByte = ($this->curByte<<1) & 0xff;
                    $this->curWeight <<= 1;
                } else {
                    //curweigh=0x80 : loose one byte
                    $this->curByte = $this->str[$lenBytes-2];
                    $this->_str = substr($this->_str, 0, -1);
                    $lenBytes--;
                    $this->curWeight = 0x80;
                }
                    
                for ($runB=$lenBytes-1; $runB>=0; $runB--) {
                    $tmpByte = ord($this->_str[$runB]);
                    $this->str[$runB] = chr(($tmpByte<<1) | $tmpLost);
                    $tmpLost = $tmpByte>>7;
                }
            } elseif ($this->curWeight==0x80) {
                return null;
            } else {
                $lostBit = ($this->curByte & 0x80)?(1):(0);
                $this->curByte = ($this->curByte<<1) & 0xff;
                $this->curWeight <<= 1;
            }
            return $lostBit;
        } else {
            // LSB
            if ($lenBytes) {
                $lostBit = (ord($this->_str[0]) & 0x01) ? (1) : (0);

                $tmpLost = ($this->curByte & $this->curWeight)?(0x80):(0);
                if ($this->curWeight!=0x01) {
                    $this->curByte = ($this->curByte>>1) & 0xff;
                    $this->curWeight >>= 1;
                } else {
                    //curweigh=0x01 : loose one byte
                    $this->curByte = $this->str[$lenBytes-2];
                    $this->_str = substr($this->_str, 0, -1);
                    $lenBytes--;
                    $this->curWeight = 0x01;
                }
                    
                for ($runB=$lenBytes-1; $runB>=0; $runB--) {
                    $tmpByte = ord($this->_str[$runB]);
                    $this->str[$runB] = chr(($tmpByte>>1) | $tmpLost);
                    $tmpLost = ($tmpByte & 0x01) << 7;
                }
            } elseif ($this->curWeight==0x01) {
                return null;
            } else {
                $lostBit = ($this->curByte & 0x01)?(1):(0);
                $this->curByte = ($this->curByte>>1) & 0xff;
                $this->curWeight >>= 1;
            }
            return $lostBit;
        }
    }    
    
    
    public function zBase32_encode()
    {
        $totalBits     = $this->getLength();
        $remainingBits = $totalBits;
        
        $byteOffset = 0;
        $bitWeight = $this->_bitWeight_first;
        
        $out = "";
        $alphabet = self::_zBase32_alphabet;
        
        // fetch 5 bits
        while ($remainingBits > 0) {
            $int = $this->_getBits(5, $totalBits, &$byteOffset, &$bitWeight);
            $out .= $alphabet[$int];
            $remainingBits -= 5;
        }
        return $out;
    }

    
    public function zBase32_decode($in)
    {
        $alphabet = self::_zBase32_alphabet;
        
        $length = strlen($in);
        for ($i=0; $i<$length; $i++) {
            $c = $in[$i];
            $c = strpos($alphabet, $c);
            if (false === $c) {
                continue;
            }
            // $c has 5 bits (high order:0x10)
            for ($i2=0; $i2<5; $i2++) {
                $bit = ($c&0x10)?1:0;
                $this->appendBit($bit);
                $c <<= 1;
            }
        }

    
    }
    
    
    private function _getBits($count, $totalBits, &$byteOffset, &$bitWeight)
    {
        $int    = 0;
        $strlen = $totalBits>>3;
        
        // get next byte
        if ($byteOffset < $strlen) {
            $curByte = ord($this->_str[$byteOffset]);
        } else {
            $curByte = $this->_curByte;
        }
        
        while ($count) {
            if ($this->_typeMsb) {
                $int <<= 1;
            } else {
                $int >>= 1;
            }
            if ($curByte & $bitWeight) {
                $int |= 1;
            }
            $bitWeight >>= 1;
            if ($bitWeight == $this->_bitWeight_empty) {
                // get next byte
                $bitWeight = $this->_bitWeight_first;
                $byteOffset++;
                if ($byteOffset < $strlen) {
                    $curByte = ord($this->_str[$byteOffset]);
                } else {
                    $curByte = $this->_curByte;
                }
            }
            $count--;
        }
        
        return $int;
    }
    

    
    
    public static function unitTest()
    {
        $bits = new Bitstring("MSB1");
        if ($bits->getLength() != 0)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="0";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        if (base64_encode($bits_string) != "AA==") { echo "ERROR\n"; }
        if ($bits->getLength() != 1)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        $zb32en = $bits->zBase32_encode();
        $bits = new Bitstring("MSB1");
        $bits->zBase32_decode($zb32en);
        $bits->zBase32_encode();
        
    
        $bits = new Bitstring("MSB1");
        $sBits="1";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "gA==") { echo "ERROR\n"; }
        if ($bits->getLength() != 1)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="01";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "QA==") { echo "ERROR\n"; }
        if ($bits->getLength() != 2)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="11";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "wA==") { echo "ERROR\n"; }
        if ($bits->getLength() != 2)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="0000000000";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "AAA=") { echo "ERROR\n"; }
        if ($bits->getLength() != 10)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="1000000010";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "gIA=") { echo "ERROR\n"; }
        if ($bits->getLength() != 10)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="10001011100010001000";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "i4iA") { echo "ERROR\n"; }
        if ($bits->getLength() != 20)  { echo "ERROR Length=".$bits->getLength()."\n"; }
    //ERROR
    //i4iA
    //0000:  8b 88 80                                          ???
    //0000:  08 b8 88                                          .¸? CLiI
    
        $bits = new Bitstring("MSB1");
        $sBits="000010001011100010001000";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "CLiI") { echo "ERROR\n"; }
        if ($bits->getLength() != 24)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        
        $bits = new Bitstring("MSB1");
        $sBits="111100001011111111000111";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "8L/H") { echo "ERROR\n"; }
        if ($bits->getLength() != 24)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="110101000111101000000100";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "1HoE") { echo "ERROR\n"; }
        if ($bits->getLength() != 24)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        $bits = new Bitstring("MSB1");
        $sBits="111101010101011110111101000011";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
    //    if (base64_encode($bits_string) != "9Ve9DA==") { echo "ERROR\n"; }
        if ($bits->getLength() != 30)  { echo "ERROR Length=".$bits->getLength()."\n"; }
    //ERROR
    //9Ve9DA==
    //0000:  f5 57 bd 0c                                       õW½.
    //0000:  3d 55 ef 43                                       =UïC
    
        
        $bits = new Bitstring("MSB1");
        $sBits="00111101010101011110111101000011";
        $bits->appendBits($sBits);
        $bits_string = $bits->__toString();
        $zb32 = $bits->zBase32_encode();
        if (base64_encode($bits_string) != "PVXvQw==") { echo "ERROR\n"; }
        if ($bits->getLength() != 32)  { echo "ERROR Length=".$bits->getLength()."\n"; }
        
        
    //    echo base64_encode($bits_string)."\n";
    //    echo Memory::dumpBin($bits_string);
    //    echo Memory::dumpBin(base64_decode("PVXvQw=="));
    
    }
    
    
    
}
