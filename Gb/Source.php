<?php
/**
 * Gb_Form
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");


/**
 * Classe Gb_Source
 */
class Gb_Source
{
    protected $_files;

    protected $_aOptions;
    
    protected $_includePath;
    
    protected $_defaultOptions;

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que pr�cis�e, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision$';
        $revision=(int) trim(substr($revision, strrpos($revision, ":")+2, -1));
        if ($mini===null) { return $revision; }
        if ($revision>=$mini) { return true; }
        if ($throw) { throw new Gb_Exception(__CLASS__." r".$revision."<r".$mini); }
        return false;
    }
    
    /**
     * Constructor
     * @param array[optional] $aOptions default options
     */
    public function __construct($aOptions=null)
    {
        $this->_aOptions=array(
            "strip_comments"=> true,                // remove all comments
            "expand_tab"=> true,                    // replace \t by spaces
            "tab_size"=>4,                          // number of spaces
            "strip_whitelines"=>true,              // strip space-only lines
            "strip_trailing_spaces"=>true,         // strip spaces at the end of all lines 
            "require_once_expand"=>true,           // Remove "require_once" and insert the file with the current options
            "require_once_expand_silent"=>false,   // Don't replace requires_once by a comment.
            "require_once_dont_expand"=>array(),   // List of files not to expand
            "reduce_indentation"=>true,            // replace tabulations by one space character
        );
        if (is_array($aOptions)) {
            $this->setOptions($aOptions);
        }
        $this->_defaultOptions=$this->getOptions();
        $this->_files=array();
        $this->_includePath=explode(PATH_SEPARATOR, get_include_path());
    }
    
    /**
     * Reset default options
     * @return Gb_Source Provides a fluent interface
     */
    public function resetDefautOptions()
    {
        $this->_aOptions=$this->_defaultOptions;
        return $this;
    }
    
    /**
     * Set options
     * @return Gb_Source Provides a fluent interface
     */
    public function setOptions(array $aOptions)
    {
        foreach ($aOptions as $key=>$val) {
            $this->_aOptions[$key]=$val;
        }
        return $this;
    }
    
    /**
     * Get default options
     * @return array;
     */
    public function getOptions()
    {
        return $this->_aOptions;
    }
    
    /**
     * Set include path
     *  @return Gb_Source Provides a fluent interface
     */
    public function setIncludePath($path)
    {
        $this->_includePath=$path;
    }
    
    
    /**
     * returns include path
     * @return array
     */
    public function getIncludePath()
    {
        return $this->_includePath;
    }
    
    
    /**
     * Add a php file
     * @param string $filename
     * @param boolean $fUseIncludePath
     * @param array[optional] $aOptions
     * @param integer[optional] position to insert the file
     * @throws Gb_Exception
     * @return Gb_Source Provides a fluent interface
     */
    public function addPhpFile($filename, $fUseIncludePath, array $aOptions=null, $position=null)
    {
        $realfilename="";
        if (is_file($filename)) {
            $realfilename=$filename;
        } elseif ($fUseIncludePath) {
            foreach ($this->getIncludePath() as $path) {
                if (is_file($path.DIRECTORY_SEPARATOR.$filename)) {
                    $realfilename=$path.DIRECTORY_SEPARATOR.$filename;
                    break;
                }
            }
        }
        if ($realfilename==="") {
            throw new Gb_Exception("File $filename cannot be found");
        }
        $realpath=realpath($realfilename);
        
        // look if the file is already in the stack
        $fAlreadyThere=false;
        foreach ($this->_files as $file) {
            if ($file[0]=="PHP_FILE" && $file[1]==$realpath) {
                $fAlreadyThere=true;
            }
        }
        
        if (!$fAlreadyThere) {
            $aOptions2=$this->getOptions();
            if (is_array($aOptions)) {
                foreach ($aOptions as $key=>$val) {
                    $aOptions2[$key]=$val;
                }
            }

            $aFile=array("PHP_FILE", $realpath, $aOptions2);
            if ($position===null) {
                $this->_files[]=$aFile;
            } else {
                $this->_files=$this->_array_insert($aFile, $this->_files, $position);
            }
        }
        return $this;
    }

    /**
     * Insert an array into another one ( FROM Gb/Util.php)
     * @param array $insert
     * @param array $into
     * @param integer $pos
     * @return array
     */
    private function _array_insert(array $insert, array $into, $pos) {
        $a1=array_slice($into, 0, $pos);
        $a1[]=$insert;
        $a2=array_slice($into, $pos);
        return array_merge($a1, $a2);
    }
    
    public function render()
    {
        $gloret="";
        
        // $this->_files may dynamically expand
        $isPhp=false;
        for ($run_file=0; $run_file<count($this->_files); $run_file++) {
            $file=$this->_files[$run_file];
            list($type, $realpath, $aOptions) = $file;
            
            if ($type==="PHP_FILE") {
                if (!$isPhp) {
                    $gloret.="<?php\n";
                    $isPhp=true;
                }
                $gloret.=$this->_renderPhpFile($realpath, $aOptions, $run_file);
            } else {
                throw new Exception("type $type unhandled");
            }
        }
        return $gloret;
    }
    
    protected function _renderPhpFile($realpath, $aOptions, $position)
    {
        $filecon=file_get_contents($realpath, false);
        $tokens=$this->_tokenize($filecon);
        unset($filecon);
        if ($aOptions["require_once_expand"]) {
            $aRequires=$this->_stripRequireOnce($tokens, $aOptions["require_once_dont_expand"]);
            foreach ($aRequires as $required_file) {
                $this->addPhpFile($required_file, true, $aOptions, $position+1);
            }
        }
        $fileout=$this->_processPhp($tokens, $aOptions);

        if (substr($fileout, 0, 1)!=="\n") {
            $fileout="\n".$fileout;
        }
        if (substr($fileout, -1)!=="\n") {
            $fileout.="\n";
        }

        return $fileout;
    }
    
    protected function _tokenize($source)
    {
        return token_get_all($source);
    }
    
    
    protected function _stripRequireOnce(array &$tokens, $aKeep=array())
    {
        $aRequires=array();
        $tokens2=array();
        $total=count($tokens);
        for ($i=0; $i<$total; $i++) {
            $token=$tokens[$i];            
            if (is_string($token)) {
                $tokens2[]=$token;
            } else {
                $id=$token[0];
                if ($id==T_REQUIRE_ONCE) {
                    $string=$line=null;
                    for ($j=$i+1; $j<$total; $j++) {
                        $token2=$tokens[$j];
                        if ($token2===";") {break; }
                        elseif ($token2=="(" || $token2==")" || (is_array($token2) && $token2[0]==T_WHITESPACE)) { }
                        elseif (is_array($token2) && $token2[0]==T_CONSTANT_ENCAPSED_STRING) {$string=$token2[1];$line=$token2[2];}
                        else { break; /* unhandled case : don't expand this require */}
                    }
                    if ($token2===";") {
                        if ($string!=null) {
                            $string=str_replace("'", "", $string);
                            $string=str_replace('"', "", $string);
                            if (!in_array($string, $aKeep)) {
                                $i=$j;     // skip those tokens
                                $aRequires[]=$string;
                                $class=__CLASS__;
                                $tokens2[]=array("Gb_Source comment", "/* require_once '$string' // Removed by $class */", $line);
                                continue; // skip to next (don't write "require_once")
                            }
                        }
                    }
                }
                $tokens2[]=$token;
            }
        }
        $tokens=$tokens2;
        return $aRequires;
    }
    

    protected function _processPhp($tokens, $aOptions)
    {
        $ret="";
        foreach ($tokens as $token) {
            if (is_string($token)) {
                // simple 1-character token
                $ret.=$token;
            } else {
                // token array
                list($id, $text) = $token;
        
                switch ($id) { 
                    case T_COMMENT: case T_DOC_COMMENT:
                        if ($aOptions["strip_comments"]) {
                            $ret=preg_replace('/[ \t]+$/', '', $ret); // removes previous spaces or tab
                            break;
                        }
                        $ret.=$text;
                        break;

                    case T_OPEN_TAG: case T_CLOSE_TAG:        // "<?php" or "? >"
                        break;

                    case T_COMMENT: case T_DOC_COMMENT:
                        if ($aOptions["strip_comments"]) {
                            $ret=preg_replace('/[ \t]+$/', '', $ret); // removes previous spaces or tab
                            break;
                        }
                        $ret.=$text;
                        break;

                    //case T_REQUIRE_ONCE:
                    //    break;
                       
                    case "Gb_Source comment":
                        if (!$aOptions["require_once_expand_silent"]) {
                            $id=T_COMMENT;
                            $ret.=$text;
                        } else {
                            $ret=preg_replace('/[ \t]+$/', '', $ret); // removes previous spaces or tab
                            if (substr($ret,-1)=="\n") {
                                $ret=substr($ret, 0, strlen($ret)-1);
                            }
                        }
                        break;
                       
                    default:
                        // anything else -> output "as is"
                        $ret.=$text;
                        break;
                }
            }
        }
        
        if ($aOptions["strip_trailing_spaces"]) {
            $ret=preg_replace('/^(\s)+$/m', '', $ret);
        }

        if ($aOptions["strip_whitelines"]) {
            $ret=str_replace("\n\n", "\n", $ret);
            $ret=str_replace("\n\n", "\n", $ret);
            $ret=str_replace("\n\n", "\n", $ret);
            $ret=str_replace("\n\n", "\n", $ret);
        }

        if ($aOptions["expand_tab"]) {
            $ret=str_replace("\t", str_repeat(" ", $aOptions["tab_size"]), $ret);
        }

        if ($aOptions["reduce_indentation"]) {
            $ret=str_replace(str_repeat(" ", $aOptions["tab_size"]), " ", $ret);
            //$ret=preg_replace('/^'.str_repeat(" ", $aOptions["tab_size"]).'/m', ' ', $ret);
        }
        
        return $ret;
    }

}
