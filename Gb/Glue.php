<?php
/**
 * Gb_Glue
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");


class Gb_Glue
{
    protected static    $_plugins=array();
    public static       $projectName="";      // Nom du projet
    protected static    $_oldSessionDir;
    protected static    $_starttime=0;

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que prÃ©cisÃ©e, ou Gb_Exception
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
     * Ajoute un plugin
     *
     * @param string $sClass nom de la classe où ajouter le plugin
     * @param string|array $fname nom de la fonction ou array(class,method)
     * @param array[optional] $aParam paramètres
     */
    public static function registerPlugin($sClass, $fname, array $aParam=array())
    {
        self::$_plugins[$sClass][]=array($fname, $aParam);
    }
    
    
    
    
    /**
     * Réinitialise tous les plugins pour une classe donnée, ou globalement
     *
     * @param string[optional] $sClass
     */
    public static function resetPlugins($sClass=null)
    {
        if ($sClass==null) {
            self::$_plugins=array();
        } else {
            self::$_plugins[$sClass]=array();
        }
    }
    
    
    
    
    /**
     * renvoie les plugins enregistrés à une classe donnée
     *
     * @param string $sClass
     * @return array
     */
    public static function getPlugins($sClass)
    {
        if (isset(self::$_plugins[$sClass])) {
            return self::$_plugins[$sClass];
        } else {
            return array();
        }
    }
    
    
    
    
  /**
   * Renvoie le nom du projet, par défaut le répertoire du script php
   *
   * @return string projectName
   */
  public static function getProjectName()
  {
    $sProjectName=self::$projectName;
    if ($sProjectName=="")
    { // Met le nom du projet sur le nom du répertoire contenant le script
      // "/gbo/gestion_e_mvc/bootstrap.php" --> "__gbo__gestion_e_mvc__bootstrap.php"
      $d="/";
      $php_self=$_SERVER["PHP_SELF"];
      $php_self=str_replace("\\", "/", $php_self);
      
      // 1: [////]   2: le répertoire    3: /    4:nomfich.php[/]
      $matches=null;
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




    public static function getStartTime()
    {
        return self::$_starttime;
    }
    
    
    
    
    public static function setStartTime()
    {
        self::$_starttime=microtime(true);
    }
    
}
?>