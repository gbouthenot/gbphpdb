<?php
/**
 * Gb_Exception
 *
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== dirname(__FILE__).DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}


/**
 * Class Gb_Exception
 *
 * @author Gilles Bouthenot
 */
class Gb_Exception extends Exception
{
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

    public function __toString()
    {
        $message = __CLASS__ . ": " . $this->getMessage() . "\n";
        $trace=$this->getTrace();

        $t = array("file"=>$this->file, "line"=>$this->line);

        for ($level = 0; $level < count($trace); $level++) {
            $file = $line = $function = "?";
            if (isset($t["file"]))     { $file=$t["file"]; }
            if (isset($t["line"]))     { $line=$t["line"]; }
            if (isset($trace[$level+0]["function"])) { $function=$trace[$level+0]["function"]; }
            $message .= "  $function(...) $file:$line\n";
            $t = $trace[$level];
        }

            return $message;
    }
}

