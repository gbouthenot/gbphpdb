<?php
require_once("Memory.php");

/**
 * Tga
 * // full specifications : http://www.ludorg.net/amnesia/TGA_File_Format_Spec.html
 * // http://local.wasp.uwa.edu.au/~pbourke/dataformats/tga/
 * @author Gilles
 *
 */
Class Tga
{
    /**
     * @var Memory
     */
    protected $_data;
    
    protected $_header;
    
    public function __construct($filename)
    {
        // $tgastr = @file_get_contents($filename);
        // if (false === $tgastr) { throw new Exception("cannot load $filename"); }
        
    	$adr = 0;
    	
        $this->_data = new Memory();
        $this->_data->bload($filename, $adr);

/*        
        for ($i=0; $i<32; $i++) {
            $chars = $this->_data->peekChars($i,1);
            echo str_pad(base_convert(ord($chars), 10, 16), 2, 0, STR_PAD_LEFT)." ";
            //echo ord($chars)." ";
        }
        echo "\n";
        for ($i=0; $i<8; $i++) {
            $chars = $this->_data->peekIntMotorola($adr+$i*4, 4); //print_r($chars);
            echo str_pad(base_convert($chars, 10, 16), 8, 0, STR_PAD_LEFT)." ";
        }
        echo "\n";
        for ($i=0; $i<8; $i++) {
            $chars = $this->_data->peekIntIntel($adr+$i*4, 4); //print_r($chars);
            echo str_pad(base_convert($chars, 10, 16), 8, 0, STR_PAD_LEFT)." ";
        }
*/
        $d = $this->_data;
        $header = array(
            "identsize"      => $d->peekIntIntel($adr + 0, 1),    // size of ID field that follows 18 byte header (0 usually)
            "colourmaptype"  => $d->peekIntIntel($adr + 1, 1),    // type of colour map 0=none, 1=has palette
            "imagetype"      => $d->peekIntIntel($adr + 2, 1),    // type of image 0=none,1=indexed,2=rgb,3=grey,+8=rle packed
            "colourmapstart" => $d->peekIntIntel($adr + 3, 2),    // first colour map entry in palette
            "colourmaplength"=> $d->peekIntIntel($adr + 5, 2),    // number of colours in palette
            "colourmapbits"  => $d->peekIntIntel($adr + 7, 1),    // number of bits per palette entry 15,16,24,32
            "xstart"         => $d->peekIntIntel($adr + 8, 2),    // image x origin
            "ystart"         => $d->peekIntIntel($adr +10, 2),    // image y origin
            "width"          => $d->peekIntIntel($adr +12, 2),    // image width in pixels
            "height"         => $d->peekIntIntel($adr +14, 2),    // image height in pixels
            "bits"           => $d->peekIntIntel($adr +16, 1),    // image bits per pixel 8,16,24,32
            "descriptor"     => $d->peekIntIntel($adr +17, 1),    // image descriptor bits (vh flip bits) If Bit 5 is set, the image will be upside down (like BMP)
        );
        
        
        if (3 != $header["imagetype"]) {
        	throw new Exception("unsupported TGA imagetype");
        } 
        if (8 != $header["bits"]) {
        	throw new Exception("unsupported TGA bits");
        } 
        
        $this->_header = $header;
        //print_r($header);
    }
    
    
    public function getPixel($x, $y)
    {
        $width     = $this->_header["width"];
        $identsize = $this->_header["identsize"]; 

        $offset = 18;
        $offset += $identsize;
        $offset += $y * $width;
        $offset += $x;
        
        return $this->_data->peekIntsMsb($offset);
    }
}
