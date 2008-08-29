<?php
/**
 * Gb_Util
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Request.php");
require_once(_GB_PATH."Response.php");

//require_once(_GB_PATH."Cache.php");
//require_once(_GB_PATH."Db.php");
//require_once(_GB_PATH."Form.php");
//require_once(_GB_PATH."Log.php");
//require_once(_GB_PATH."Session.php");
//require_once(_GB_PATH."Response.php");
//require_once(_GB_PATH."String.php");
//require_once(_GB_PATH."Timer.php");

/**
 * class Gb_Util
 *
 * @author Gilles Bouthenot
 * @version 1.01
 *  function str_readfile($filename) : à remplacer par file_get_contents($filename)
 *
 */
Class Gb_Util
{
  // ***********************
  // variables statiques accessibles depuis l'extérieur avec Gb_Util::$head et depuis la classe avec self::$head
  // ************************

  public static $debug=0;                 // par défaut, pas de mode débug
  public static $forbidDebug=0;           // ne pas interdire de passer en débug par $_GET["debug"]

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision$';
        $revision=trim(substr($revision, strrpos($revision, ":")+2, -1));
        if ($mini===null) { return $revision; }
        if ($revision>=$mini) { return true; }
        if ($throw) { throw new Gb_Exception(__CLASS__." r".$revision."<r".$mini); }
        return false;
    }
    

  /**
   * Cette classe ne doit pas être instancée !
   */
  private function __construct()
  {
  }
  

  /**
   * Initialise gzip, error_reporting, APPELLE main(), affiche le footer et quitte
   *
   * Met error_reporting si debug, ou bien si _GET["debug"] (sauf si forbidDebug)
   * Appelle main()
   * Si débug (ou showFooter), affiche le footer
   *
   * @param string[optional] $function fonction à appeler (main si non précisé)
   */
  public static function startup($function="main", $param=array())
  {
    Gb_Glue::setStartTime();

    if (Gb_Response::$preventGzip==0)
      ob_start("ob_gzhandler");

    error_reporting(E_ERROR);
    if ( Gb_Util::$debug || (Gb_Request::getFormGet("debug") && !self::$forbidDebug) )
    {
      error_reporting( E_ALL | E_STRICT );
      Gb_Util::$debug=1;
    }
    else
      Gb_Util::$debug=0;

    Gb_Glue::registerPlugin("Gb_Response_Footer", array(__CLASS__, "GbResponsePlugin"));

    if (is_array($function) || function_exists($function))
      call_user_func_array($function, $param);
    else
      throw new Gb_Exception("function main() does not exist !");

        if (Gb_Util::$debug || Gb_Response::$show_footer) {
      Gb_Response::send_footer();
        }
        
        Gb_Response::close_page();
    exit(0);
  }
  

  /**
   * Combine deux arrays
   * Idem que array_merge, mais sans renuméroter les clés si clé numérique (évite de concaténer)
   *
   * @param array $arr1
   * @param array $arr2
   * @return array
   */
  public static function array_merge(array $arr1, array $arr2)
  {
    // si arr2 est plus grand, échange arr1 et arr2 pour itérer sur le plus petit
    if (count($arr2)>count($arr1)) {
      list($arr1, $arr2)=array($arr2, $arr1);
    }
    foreach ($arr2 as $k=>$v)
      $arr1[$k]=$v;
    return $arr1;
  }










  


  
  
    
  /**
   * Inclut et execute un fichier et retourne le résultat dans une chaine
   *
   * @param string $file fichier à include
   * @return string le fichier
   * @throws Gb_Exception
   */
  public static function include_file($file)
  { // fait include d'un fichier, mais retourne le résultat dans une chaine
    // cela permet d'inclure un fichier et de l'éxecuter en même temps
    if (file_exists($file) && is_readable($file)) {
      ob_start();
      include($file);
      return  ob_get_clean();
    } else {
      throw new Gb_Exception("Fichier $file inaccessible");
    }
  }

  /**
   * renvoie ?debug=1 ou &debug=1 si debug activé
   *
   * @param string $c caractère à renvoyer ("&" (par défaut) ou "?")
   * @return string
   */
  public static function url_debug($c="&")
  {
    if (self::$debug)
      return $c."debug=".self::$debug;
    return "";
  }


  


  public static function roundCeil($num, $nbdigits=3)
  {
    if (empty($nbdigits))
      return $num;

//    $mul=pow(10,$nbdigits);
    $div=0;
    $num2=0;
    if ($num>.1)while($num>1) {$num/=10; $div++;}
    else        while($num<.1){$num*=10; $div--;}
    do {
      $num*=10;
      $nbdigits--;
      if ($nbdigits==0) $num+=0.999999;
      $digit=intval($num);
      $num-=$digit;
      $num2=$num2*10+$digit;
      $div--;
    } while ($nbdigits>0);

    $num2*=pow(10, $div);
    return $num2;
  }








    public static function GbResponsePlugin()
    {
        $ret="";

        $totaltime=microtime(true)-Gb_Glue::getStartTime();
        $totaltime=Gb_Util::roundCeil($totaltime);
        $ret.="Total time: {$totaltime}s";
        
        return $ret;
      }
  



    protected static $_tmpdir;

    public static function sys_get_temp_dir()
    {
        if (self::$_tmpdir===null) {
           // add support of sys_get_temp_dir for PHP4/5, use the following code:
           // Based on http://www.phpit.net/
           // article/creating-zip-tar-archives-dynamically-php/2/
    
            if ( function_exists('sys_get_temp_dir')) {
                self::$_tmpdir=sys_get_temp_dir();
            } else {
                // Try to get from environment variable
               if ( !empty($_ENV['TMP']) ) {
                   return realpath( $_ENV['TMP'] );
               } elseif ( !empty($_ENV['TMPDIR']) ) {
                   return realpath( $_ENV['TMPDIR'] );
               } elseif ( !empty($_ENV['TEMP']) ) {
                   return realpath( $_ENV['TEMP'] );
               } else {
                   // Detect by creating a temporary file
                   // Try to use system's temporary directory
                   // as random name shouldn't exist
                   $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
                   if ( $temp_file ) {
                       $temp_dir = realpath( dirname($temp_file) );
                       unlink( $temp_file );
                       self::$_tmpdir=$temp_dir;
                   } else {
                       return FALSE;
                   }
               }
            }
        }
        return self::$_tmpdir;
    }



}

