<?php
/**
 * Gb_Request
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

require_once(_GB_PATH."Exception.php");


class Gb_Request
{
    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
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
     * Enl�ve les slashes des donn�es GET, POST, COOKIE (gpc), si magic_quote_gpc est actif
     *
     * @param string $str cha�ne � traiter
     * @return string $str avec �ventuellement stripslashes
     */
    public static function gpcStripSlashes($str)
    {
        if ( get_magic_quotes_gpc() ) {
            if ( is_array($str) ) {
                array_walk_recursive($str, array(__CLASS__, "gpcStripSlashesArray"));
                return $str;
            } else {
                return stripslashes($str);
            }
        }
        return $str;
    }


    /**
     * Renvoie la valeur POST, sans slash ou false si elle n'est pas d�finie
     *
     * @param string $index valeur � chercher
     * @param mixed[optional] $default
     * @return mixed $_POST[$index]
     */
    public static function getFormPost($index, $default=false)
    {
        if ( isset($_POST[$index]) ) {
            return self::gpcStripSlashes($_POST[$index]);
        } else {
            return $default;
        }
    }


    /**
     * Renvoie la valeur GET, sans slash ou false si elle n'est pas d�finie
     *
     * @param string $index valeur � chercher
     * @param mixed[optional] $default
     * @return mixed $_GET[$index]
     */
    public static function getFormGet($index, $default=false)
    {
        if ( isset($_GET[$index]) ) {
            return self::gpcStripSlashes($_GET[$index]);
        } else {
            return $default;
        }
    }


    /**
     * Renvoie la valeur POST, sans slash ou la valeur GET ou false si elles ne sont par d�finies
     *
     * @param string $index valeur � chercher
     * @param mixed[optional] $default
     * @return mixed $_POST/$_GET[$index]
     */
    public static function getForm($index, $default=false)
    {
        if ( isset($_POST[$index]) ) {
            return self::gpcStripSlashes($_POST[$index]);
        } elseif ( isset($_GET[$index]) ) {
            return self::gpcStripSlashes($_GET[$index]);
        } else {
            return $default;
        }
    }


    private static function gpcStripSlashesArray(&$input, $key)
    {
        $key=stripslashes($key);
        $input=stripslashes($input);
    }
}
