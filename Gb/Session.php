<?php
/**
 */

if (!defined("_GB_PATH")) {
  define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Request.php");
require_once(_GB_PATH."Util.php");

Class Gb_Session
{
    public static $sessionDir="";           // Répertoire des sessions par défaut session_path/PROJECTNAME/sessions

    /**
   * Renvoit le nom du repertoire de la session
   * crée le répertoire si besoin
   *
   * @return string sessionDir
   * @throws Gb_Exception
   */
    public static function getSessionDir()
    {
        if ( self::$sessionDir=="" ) {
            $updir=Gb_Util::getOldSessionDir();
            $updir2=$updir.DIRECTORY_SEPARATOR.Gb_Util::getProjectName();
            if ( (!is_dir($updir2) || !is_writable($updir2)) && is_dir($updir) && is_writable($updir) )
                @mkdir($updir2, 0700);
            $updir3=$updir2.DIRECTORY_SEPARATOR."sessions";
            if ( (!is_dir($updir3) || !is_writable($updir3)) && is_dir($updir2) && is_writable($updir2) )
                @mkdir($updir3, 0700);
            if ( !is_dir($updir3) || !is_writable($updir3) )
                throw new Gb_Exception("Impossible de créer le répertoire $updir3 pour stocker les sessions ! session_save_path()=$updir");
            session_save_path($updir3);
            self::$sessionDir=$updir3;
        }
        return self::$sessionDir;
    }

  /**
   * Démarre une session sécurisée (id changeant, watch ip et l'user agent)
   * Mettre echo Gb_Util::session_start() au début du script.
   *
   * @param int[optional] $relTimeOutMinutes Timeout depuis la dernière page (1h défaut)
   * @param int[optional] $grandTimeOutMinutes Timeout depuis création de la session (6h défaut)
   * @throws Gb_Exception si impossible de créer répertoire pour le sessions
   * @return string texte de warning ou ""
   */
  public static function session_start($relTimeOutMinutes=60, $grandTimeOutMinutes=360)
  {
    session_name(Gb_Util::getProjectName()."_PHPID");
    self::getSessionDir();
    session_start();

    $client=md5("U:".$_SERVER["HTTP_USER_AGENT"]." IP:". $_SERVER["REMOTE_ADDR"]);

    $sVarName=__CLASS__."_client";
    $sVarNameUniqId=__CLASS__."_uniqId";
    $sVarNameGrandTimeout=__CLASS__."_grandTimeout";
    $sVarNameRelTimeout=__CLASS__."_relTimeout";

    $sWarning="";

    $uniqId=Gb_Request::getForm("uniqId");
    if ( isset($_SESSION[$sVarName]) && $_SESSION[$sVarName]!=$client )
    { // session hijacking ? Teste l'IP et l'user agent du client
      $_SESSION=array();
      session_regenerate_id(true);
      $sWarning.="<b>Votre adresse IP ou votre navigateur a changé depuis la dernière page demandée.";
      $sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
    }
    elseif( strlen($uniqId) && isset($_SESSION[$sVarNameUniqId]) && $uniqId != $_SESSION[$sVarNameUniqId] )
    { // session hijacking ? Teste l'uniqId du formulaire (ou get)
      $_SESSION=array();
      session_regenerate_id(true);
      $sWarning.="<b>Votre session n'est pas authentifiée";
      $sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
    }
    elseif( (isset($_SESSION[$sVarNameGrandTimeout]) && time()>$_SESSION[$sVarNameGrandTimeout])
         || (isset($_SESSION[$sVarNameRelTimeout])   && time()>$_SESSION[$sVarNameRelTimeout])     )
    {
      $_SESSION=array();
      session_regenerate_id(true);
      $sWarning.="<b>Votre session a expiré";
      $sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
    }

    if (empty($_SESSION[$sVarName]))
    { // premier appel de la session: initialisation  du client
      if (strlen($uniqId)==0)
      { // génére un uniqId ni n'existe pas déjà, sinon reprend l'ancien
        $a='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $u=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)};
        $uniqId=$u;
      }
      $_SESSION[$sVarNameUniqId]=$uniqId;
      $_SESSION[$sVarName]=$client;
      $_SESSION[$sVarNameGrandTimeout]=time()+60*$grandTimeOutMinutes;
    }
    elseif (rand(1, 100)<=20)
    { // 20% de chance de regénérer l'ID de session
      session_regenerate_id(true);
    }

    $_SESSION[$sVarNameRelTimeout]=time()+60*$relTimeOutMinutes;

    return $sWarning;
  }
  
    
}