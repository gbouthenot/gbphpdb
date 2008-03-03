<?php
/**
 * 
 */

if (!defined("_GB_PATH")) {
  define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Request.php");
require_once(_GB_PATH."Response.php");

require_once(_GB_PATH."Cache.php");
require_once(_GB_PATH."Db.php");
require_once(_GB_PATH."Form.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Session.php");
require_once(_GB_PATH."Response.php");
require_once(_GB_PATH."String.php");
require_once(_GB_PATH."Timer.php");

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
  const Gb_UtilVERSION="2alpha";

  // ***********************
  // variables statiques accessibles depuis l'extérieur avec Gb_Util::$head et depuis la classe avec self::$head
  // ************************

  public static $projectName="";      // Nom du projet
  public static $debug=0;                 // par défaut, pas de mode débug
  public static $forbidDebug=0;           // ne pas interdire de passer en débug par $_GET["debug"]
  public static $starttime=0;
  protected static $_oldSessionDir;


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
    self::$starttime=microtime(true);

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

    if (is_array($function) || function_exists($function))
      Gb_Log::log_function(Gb_Log::LOG_DEBUG, "", $function, $param);
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
   * Idem que array_merge, mais sans renuméroter les clés si clé numérique
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










  /**
   * Renvoit le nom du projet, par défaut le répertoire du script php
   *
   * @return string projectName
   */
  public static function getProjectName()
  {
    $sProjectName=self::$projectName;
    if ($sProjectName=="")
    { // Met le nom du projet sur le nom du répertoire contenant le script
      // "/gbo/gestion_e_mvc/bootstrap.php" --> "__gbo__gestion_e_mvc__bootstrap.php"
      $d=DIRECTORY_SEPARATOR;
      $php_self=$_SERVER["PHP_SELF"];
      // 1: [////]   2: le répertoire    3: /    4:nomfich.php[/]
      unset($matches);
      preg_match("@^($d*)(.*)($d+)(.+$d*)$@", $php_self, $matches);
      if (isset($matches[2]) && strlen($matches[2]))
        $sProjectName=str_replace($d, "__", $matches[2]);
      else
          $sProjectName=basename($php_self);
      self::$projectName=$sProjectName;
    }
    return $sProjectName;
  }




    public static function getOldSessionDir()
    {
        if ( self::$_oldSessionDir===null ) {
            self::$_oldSessionDir=session_save_path();
            if (self::$_oldSessionDir=="") {
                self::$_oldSessionDir=".";
            }
        }
        return self::$_oldSessionDir;
    }

}

