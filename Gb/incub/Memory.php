<?php

/**
 * Provide Random-Access Memory simulation
 * You can write at arbitrary addresses. We use 256-byte blocks so writing "a" at 0x0000000, then "b"
 * at 0xacbdef0 will only consume 512 bytes (2 blocks).
 * @author Gilles Bouthenot
 */
Class Memory
{
    protected $_data=array();
    protected $_length=0;
    
    /**
     * write string(s) to the specified memory address.
     * If no adr specified, write at the END of the memory
     * @param integer|array|null $adr
     * @param string|array $chars can be "a", "ab" or array("a", "ab")
     * @return Memory
     */
    public function pokeChars($adr, $chars)
    {
    	if (null === $adr) {
    		$adr = $this->_length;
    	}
    	
        if (is_array($chars)) {
            $chars = implode("", $chars);
        }
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;
        
        for ($i=0, $len=strlen($chars); $i<$len; $i++) {
            $this->_poke($blkNumber, $offset++, $chars[$i]);
            $adr++;
            if ( $offset == 256 ) {
                $offset = 0;
                $blkNumber++;
            }
        }
        
		// update length
        if ($adr > $this->_length) {
        	$this->_length = $adr;
        }
        
        return $this;
    }

    /**
     * Read memory bytes and return a string
     * if len=omitted/null, read 1 byte.
     * if len=0,            read until 0x00 is encountered (do not send the final 0x00)
     * @param integer $adr
     * @param integer[optional] $len number of bytes to read (default 1), OR 0 to stop at the first 0x00
     * @return string
     */
    public function peekChars($adr, $len=null)
    {
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;

        if (null === $len) {
            $len = 1;
        } elseif (0 === $len) {
            $len = null;
        }
        
        $chars="";
        
        while ( ($len===null) || ($len>0) ) {
            $char = $this->_peek($blkNumber, $offset++);
            if ( $offset == 256 ) {
                $offset = 0;
                $blkNumber++;
            }
            
            // end of string ?
            if ($len===null) {
                if ($char===chr(0)) {
                    return $chars;
                }
                $chars .= $char;
            } else {
                $chars .= $char;
                $len--;
            }

        }
        
        return $chars;
    }
    
    
    /**
     * Enter description here ...
     * @param integer|array $adr
     * @param integer       $width
     * @param integer|array $values
     * @return Memory
     */
    public function pokeIntsMsb($adr, $width, $values)
    {
        if (!is_array($values)) {
            $values=array($values);
        }
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;
        
        $masks=array( array(0x000000ff, 0), array(0x0000ff00, 8), array(0x00ff0000, 16), array(0xff000000, 24) );
        
        foreach ($values as $value) {
            $w = $width;
            while ($w--) {
                list($mask, $dec)  = $masks[$w];
                $val = ($value & $mask) >> $dec;
                
                $this->_poke($blkNumber, $offset++, chr($val));
                $adr++;
                if ( $offset == 256 ) {
                    $offset = 0;
                    $blkNumber++;
                }
            }
        }
        
		// update length
        if ($adr > $this->_length) {
        	$this->_length = $adr;
        }

        return $this;
    }
    
    /**
     * Enter description here ...
     * @param integer|array $adr
     * @param integer       $width
     * @param integer|array $values
     * @return Memory
     */
    public function pokeIntsLsb($adr, $width, $values)
    {
        if (!is_array($values)) {
            $values=array($values);
        }
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;
        
        foreach ($values as $value) {
            $w = $width;
            while ($w--) {
                $val = ($value & 0xff);
                $value >>= 8;
                
                $this->_poke($blkNumber, $offset++, chr($val));
                $adr++;
                if ( $offset == 256 ) {
                    $offset = 0;
                    $blkNumber++;
                }
            }
        }
        
		// update length
        if ($adr > $this->_length) {
        	$this->_length = $adr;
        }

        return $this;
    }
    
    
    public function peekIntsMsb($adr, $width=1, $number=null)
    {
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;
        
        $values=array();
        $number2 = $number;
        if (null === $number) {
            $number2 = 1;
        }
        for ($i=0; $i<$number2; $i++) {
            $value=0;
            for ($j=0; $j<$width; $j++) {
                $value <<= 8;
                $peek = $this->_peek($blkNumber, $offset++);
                $value += ord($peek);
                if ( 256 == $offset ) { $offset = 0; $blkNumber++; }
            }
            $values[] = $value;
        }
        
        if (null === $number) {
            return $values[0];
        }
        return $values;
    }

    
    
    public function peekIntsLsb($adr, $width=1, $number=null)
    {
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;
        
        $values=array();
        $number2 = $number;
        if (null === $number2) {
            $number2 = 1;
        }
        for ($i=0; $i<$number2; $i++) {
            $value = 0;
            $dec = 0;
            for ($j=0; $j<$width; $j++) {
                $peek = $this->_peek($blkNumber, $offset++);
                $peek = ord($peek);
                $value += $peek<<$dec;
                if ( 256 == $offset ) { $offset = 0; $blkNumber++; }
                $dec += 8;
            }
            $values[] = $value;
        }
        
        if (null === $number) {
            return $values[0];
        }
        return $values;
    }
    
    
    
    public function peekIntsMsb_decstr($adr, $width=1, $number=null)
    {
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;
        
        $values=array();
        $number2 = $number;
        if (null === $number) {
            $number2 = 1;
        }
        for ($i=0; $i<$number2; $i++) {
            $value="0";
            for ($j=0; $j<$width; $j++) {
                $value = bcmul($value, 256);
                $peek = $this->_peek($blkNumber, $offset++);
                $value = bcadd($value, ord($peek));
                if ( 256 == $offset ) { $offset = 0; $blkNumber++; }
            }
            $values[] = $value;
        }
        
        if (null === $number) {
            return $values[0];
        }
        return $values;
    }

    
    
    public function peekIntsLsb_decstr($adr, $width=1, $number=null)
    {
        $coords = $this->_getDataCoords($adr);
        list($blkNumber, $offset) = $coords;
        
        $values=array();
        $number2 = $number;
        if (null === $number2) {
            $number2 = 1;
        }
        for ($i=0; $i<$number2; $i++) {
            $value = "0";
            $mul = "1";
            for ($j=0; $j<$width; $j++) {
                $peek = $this->_peek($blkNumber, $offset++);
                $peek = bcmul(ord($peek), $mul);
                $value = bcadd($value, $peek);
                if ( 256 == $offset ) { $offset = 0; $blkNumber++; }
                $mul = bcmul($mul, 256);
            }
            $values[] = $value;
        }
        
        if (null === $number) {
            return $values[0];
        }
        return $values;
    }
    
    
    
    public static function binToHexstr($rawstring)
    {
        return array_shift(unpack("H*", $rawstring));
    }

    
    public static function hexstrToBin($hexstr)
    {
        $hexstr = str_replace(' ', '', $hexstr);
        $hexstr = str_replace('\x', '', $hexstr);
        return pack('H*', $hexstr);
    }
    
    public static function binstrMsbToBin($binstr)
    {
        $binstr = str_replace(' ', '', $binstr);
        
        $ret="";
        $weight = 0x80;
        $byte   = 0;
        for($i=0,$l=strlen($binstr); $i<$l;) {
            $byte |= (substr($binstr,$i,1)) ? ($weight) : (0);
            $weight >>= 1;
            if ((++$i==$l)||($weight==0)) {
                $ret .= chr($byte);
                $weight = 0x80;
                $byte = 0;
            }
        }
        return $ret;
    }

    public static function binstrLsbToBin($binstr)
    {
        $binstr = str_replace(' ', '', $binstr);
        
        $ret="";
        $weight = 1;
        $byte   = 0;
        for($i=0,$l=strlen($binstr); $i<$l;) {
            $byte |= (substr($binstr,$i,1)) ? ($weight) : (0);
            $weight <<= 1;
            if ((++$i==$l)||($weight==256)) {
                $ret .= chr($byte);
                $weight = 1;
                $byte = 0;
            }
        }
        return $ret;
    }
    
    /**
     * Print a hex dump of a string
     * 49 4e 46 4f 00 00 00 10 54 61 68 6f 6d 61 00 62     INFO....Tahoma.b
     * 69 31 33 00 d0 b0 00 00                             i13.....
     * @param string $string
     * @return string
     */
    public static function dumpBin($rawstr)
    {
        $ret=$a=$h="";
        for ($i=0,$l=min(65536,strlen($rawstr))-1;$i<=$l;$i++) {
            $c=ord(substr($rawstr,$i,1));
            $h.=str_pad(dechex($c),2,0,STR_PAD_LEFT)." ";
            $a.=($c<32)?("."):(utf8_encode(chr($c)));
            if (($i==$l)||(($i&15)==15)) {
                $ret.=str_pad(dechex($i&0xfff0),4,0,STR_PAD_LEFT).":  ".str_pad($h,50)."$a\n";
                $a=$h="";
            }
        }
        return $ret;
    }
    
    
    
    public function bload($filename, $adr)
    {
        $filesize = @filesize($filename);
        if ( false === $filesize ) {
            throw new Exception("cannot load $filename");
        }
        if ($filesize == 0) {
            return;
        }
        
        $fhandle = fopen($filename, "rb");
        while ( $filesize > 0 ) {
            $len = min(1024, $filesize);

            $chars = fread($fhandle, $len);
            if ( false === $chars ) {
                fclose($fhandle);
                throw new Exception("Error reading $filename");
            }
            
            $this->pokeChars($adr, $chars);

            $filesize -= $len;
            $adr      += $len;
        }
        fclose($fhandle);
    }
    
    
    protected function _initBlock($blkNumber)
    {
        if (!isset($this->_data[$blkNumber])) {
            $this->_data[$blkNumber] = pack("H*", str_repeat("00", 256));
        }
        return;
    }
    
    /**
     * Renvoie le block demandé
     * @param int|array $blkNumber
     */
    protected function _getBlock($blkNumber)
    {
        if (is_array($blkNumber)) {
            $blkNumber = $blkNumber[0];
        }
        $this->_initBlock($blkNumber);
        return $this->_data[$blkNumber];
    }
    
    /**
     * convert an absolute address into (blockNumber, offset)
     * @param integer|array $adr
     */
    protected function _getDataCoords($adr)
    {
        if (is_array($adr)) {
            return $adr;
        }
        $blkNumber = $adr >> 8;
        $offset    = $adr & 255;
        return array($blkNumber, $offset);
    }
    
    /**
     * LOW LEVEL Poke a byte (char)
     * @param integer $blkNumber
     * @param integer $offset
     * @param string $value
     */
    protected function _poke($blkNumber, $offset, $value)
    {
        $this->_initBlock($blkNumber);
        $this->_data[$blkNumber][$offset] = $value;
    }

    /**
     * Peek un octet (char)
     * @param integer $blkNumber
     * @param integer $offset
     */
    protected function _peek($blkNumber, $offset)
    {
        $this->_initBlock($blkNumber);
        return $this->_data[$blkNumber][$offset];
    }
    
//function readIntel(&$s, $off,   $len)
//{
//    for ($value=0, $i=0; $i<$len; $i++)
//        $value+=ord($s[$off+$i])<<($i<<3);
//    return $value;
//}


    
    public function unitTest()
    {
        $M=new Memory();
        $M->pokeChars(123456, "Bonjour this is a test");
        $M->pokeChars(124000, "Bonjour this is a test");
    
        $ret = $M->peekChars(123456, 2000);
        // $ret : s:2000:"Bonjour this is a testBonjour this is a test";    (with 0x00 bytes)
        assert('md5(serialize($ret))=="95e91c3df7ecb1bb4bf05e8e8f735f6b"');
        assert('strlen($ret)==2000');
    
        $ret = $M->peekChars(123456, 0);
        $ass=<<<HEREDOC
serialize(\$ret) == 's:22:"Bonjour this is a test";'
HEREDOC;
    	assert($ass);
    
        $ret = $M->peekChars(124000, 10);
        $ass=<<<HEREDOC
serialize(\$ret) == 's:10:"Bonjour th";'
HEREDOC;
    	assert($ass);
    
        $ret = $M->peekChars(124001);
        $ass=<<<HEREDOC
serialize(\$ret) == 's:1:"o";'
HEREDOC;
    	assert($ass);
    	
    	
        $M=new Memory();
        $M->pokeIntsMsb(0xff8240, 2, array(
            0x7ee, 0x530, 0x750, 0x310,
            0x919, 0x530, 0x750, 0x310,
            0x34e, 0x530, 0x750, 0x310,
            0xe43, 0x530, 0x750, 0x310,
        ));
        $M->pokeIntsMsb(0x3f8000+ 0*160, 4, 0x07e007e0);
        $M->pokeIntsMsb(0x3f8000+15*160, 4, 0x07e007e0);
        $M->pokeIntsMsb(0x3f8000+ 1*160, 4, 0x0ff00ff0);
        $M->pokeIntsMsb(0x3f8000+14*160, 4, 0x0ff00ff0);
        $M->pokeIntsMsb(0x3f8000+ 2*160, 4, 0x3ffc3ffc);
        $M->pokeIntsMsb(0x3f8000+13*160, 4, 0x3ffc3ffc);
        $M->pokeIntsMsb(0x3f8000+ 3*160, 4, 0x7ffe7ffe);
        $M->pokeIntsMsb(0x3f8000+12*160, 4, 0x7ffe7ffe);
        $M->pokeIntsMsb(0x3f8000+ 4*160, 4, 0x7ffe61fe);
        $M->pokeIntsMsb(0x3f8000+11*160, 4, 0x7ffe61fe);
        $M->pokeIntsMsb(0x3f8000+ 5*160, 4, 0xffffc0ff);
        $M->pokeIntsMsb(0x3f8000+10*160, 4, 0xffffc0ff);
        $M->pokeIntsMsb(0x3f8000+ 6*160, 4, 0xc3fffc7f);
        $M->pokeIntsMsb(0x3f8000+ 7*160, 4, 0xc3fffc7f);
        $M->pokeIntsMsb(0x3f8000+ 8*160, 4, 0xc3fffc7f);
        $M->pokeIntsMsb(0x3f8000+ 9*160, 4, 0xc3fffc7f);
        
        
        
        // compose raw string
        $pi1  = chr(0);
        $pi1 .= chr(0);
        $palette = $M->peekChars(0xff8240, 32);
        $pi1 .= $palette;
        $pi1 .= $M->peekChars(0x3f8000, 32000);
        
        //file_put_contents("test.pi1", $pi1);
    
        $md5 = md5($pi1);
        assert('$md5=="4a02f1e19e4b594de2d453c19bf662d2"');
    
        $ass1 = Memory::binToHexstr($palette);
        assert('$ass1 == "07ee0530075003100919053007500310034e0530075003100e43053007500310"');
        $ass2 = Memory::hexstrToBin($ass1);
        assert('$ass2 == $palette');
        
        $ass1 = Memory::dumpBin($palette);
        $ass2 = <<<HEREDOC
0000:  07 ee 05 30 07 50 03 10 09 19 05 30 07 50 03 10   .î.0.P.....0.P..
0010:  03 4e 05 30 07 50 03 10 0e 43 05 30 07 50 03 10   .N.0.P...C.0.P..

HEREDOC;
    
        assert('serialize($ass1) == serialize($ass2)');
        
        $M->pokeIntsMsb(0, 2, 0x1234);
        assert("\$M->peekChars(0) == chr(0x12)");
        assert("\$M->peekChars(1) == chr(0x34)");
        $ass1 = $M->peekIntsMsb(0, 2);
        $ass2 = 0x1234; 
        assert('serialize($ass1) == serialize($ass2)');
        
        $M->pokeIntsLsb(0, 2, 0x1234);
        assert("\$M->peekChars(0) == chr(0x34)");
        assert("\$M->peekChars(1) == chr(0x12)");
        $ass1 = $M->peekIntsLsb(0, 2);
        $ass2 = 0x1234; 
        assert('serialize($ass1) == serialize($ass2)');
            
        $M->pokeIntsMsb(0, 4, 0xfedcba98);
        assert("\$M->peekChars(0) == chr(0xfe)");
        assert("\$M->peekChars(1) == chr(0xdc)");
        assert("\$M->peekChars(2) == chr(0xba)");
        assert("\$M->peekChars(3) == chr(0x98)");
        $ass1 = $M->peekIntsMsb(0, 4);
        $ass2 = 0xfedcba98;
        assert('serialize($ass1) == serialize($ass2)');
        
        $M->pokeIntsLsb(0, 4, 0xfedcba98);
        assert("\$M->peekChars(3) == chr(0xfe)");
        assert("\$M->peekIntsMsb(2) == 0xdc");
        assert("\$M->peekIntsLsb(1) == 0xba");
        assert("\$M->peekChars(0) == chr(0x98)");
        $ass1 = $M->peekIntsLsb(0, 4);
        $ass2 = 0xfedcba98;
        assert('serialize($ass1) == serialize($ass2)');
        
        $ass1 = $M->peekIntsLsb(0, 2, 2);
        $ass2 = array(        0xba98,         0xfedc);
        assert('serialize($ass1) == serialize($ass2)');
    
        $ass1 = $M->peekIntsLsb_decstr(0, 2, 2);
        $ass2 = array((string)0xba98, (string)0xfedc);
        assert('serialize($ass1) == serialize($ass2)');
            
        
        /*
        0000011111100000
        0001111111111000
        0011111111111100
        0111111111111110
        0112221111111110
        1122222111111111
        1233322211111111
        1233322211111111
        
        
        couleur 0: 10+ 6+ 4+ 2+ 2+ 0+ 0x2  =  24 --> toujours 0
        couleur 1:  6+10+12+14+11+11+ 9x2  =  82 --> 1 : permet de faire des move.w (mais nécessite and.w pour les or !)
        couleur 2:  0+ 0+ 0+ 0+ 3+ 5+ 4x2  =  16 --> 3 : permet de 
        couleur 3:  0+ 0+ 0+ 0+ 0+ 0+ 3x2  =   6
        */
        
        
        $aSprData=array(
        "0000011111100000",
        "0001111111111000",
        "0011111111111100",
        "0111111111111110",
        "0112221111111110",
        "1122222111111111",
        "1233322211111111",
        );
        
        for ($dec=0; $dec<16; $dec++){
    //        echo "Décalage $dec:\n";
            foreach ($aSprData as $sprData) {
                $sprData = str_repeat("0", $dec) . $sprData . str_repeat("0",16);
                for ($long=0; $long<2; $long++) {
                    $weight = 0x8000;
                    $word0 = $word1 = 0;
                    for ($pixel=0; $pixel<16; $pixel++) {
                        $pixnumber = $long*16 + $pixel;
                        $pixcol    = substr($sprData, $pixnumber, 1);
                        if (1 == $pixcol) {
                            $word0 |= $weight;
                            $word1 |= $weight;
                        } elseif (2 == $pixcol) {
                            $word0 |= $weight;
                        }elseif (3 == $pixcol) {
                            $word1 |= $weight;
                        }
                        $weight >>= 1;
                    }
    //                echo str_pad(base_convert($word0, 10, 16), 4, "0", STR_PAD_LEFT)." ".str_pad(base_convert($word1, 10, 16), 4, "0", STR_PAD_LEFT)."    ";
                }
    //            echo"\n";
            }
        }
    }
        


}







































































require_once("Bitstring.php");

Memory::unitTest();
BitString::unitTest();

