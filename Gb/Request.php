<?php
/**
 */
class Gb_Request
{


    /**
     * Enlève les slashes des données GET, POST, COOKIE (gpc), si magic_quote_gpc est actif
     *
     * @param string $str chaïne à traiter
     * @return string $str avec éventuellement stripslashes
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
     * Renvoie la valeur POST, sans slash ou false si elle n'est pas définie
     *
     * @param string $index valeur à chercher
     * @return string|false $_POST[$index]
     */
    public static function getFormPost($index)
    {
        if ( isset($_POST[$index]) ) {
            return self::gpcStripSlashes($_POST[$index]);
        } else {
            return false;
        }
    }


    /**
     * Renvoie la valeur GET, sans slash ou false si elle n'est pas définie
     *
     * @param string $index valeur à chercher
     * @return string|false $_GET[$index]
     */
    public static function getFormGet($index)
    {
        if ( isset($_GET[$index]) ) {
            return self::gpcStripSlashes($_GET[$index]);
        } else {
            return false;
        }
    }


    /**
     * Renvoie la valeur POST, sans slash ou la valeur GET ou false si elles ne sont par définies
     *
     * @param string $index valeur à chercher
     * @return string|false $_POST/$_GET[$index]
     */
    public static function getForm($index)
    {
        if ( isset($_POST[$index]) ) {
            return self::gpcStripSlashes($_POST[$index]);
        } elseif ( isset($_GET[$index]) ) {
            return self::gpcStripSlashes($_GET[$index]);
        } else {
            return false;
        }
    }


    private static function gpcStripSlashesArray(&$input, $key)
    {
        $key=stripslashes($key);
        $input=stripslashes($input);
    }
}
