<?php
/**
 * Gb_Session
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Glue.php");

require_once(_GB_PATH."String.php");
require_once(_GB_PATH."Log.php");

Class Gb_Session
{
    public static $sessionDir="";           // Répertoire des sessions par défaut session_path/PROJECTNAME/sessions
    
    protected static $grandTimeOutMinutes;
    
    
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
   * Renvoie le nom du repertoire de la session
   * crée le répertoire si besoin
   *
   * @return string sessionDir
   * @throws Gb_Exception
   */
    public static function getSessionDir()
    {
        if ( self::$sessionDir=="" ) {
            $updir=Gb_Glue::getOldSessionDir();
            $updir2=$updir.DIRECTORY_SEPARATOR.Gb_Glue::getProjectName();
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
    
    
    
    
    public static function getUniqId()
    {
        if (isset($_SESSION["Gb_Session"]))
            return $_SESSION["Gb_Session"]["uniqId"];
        else
            return "";
    }
    
    
    
    
   /**
    * Démarre une session sécurisée (id changeant, watch ip et l'user agent)
    * Mettre echo Gb_Session::session_start() au début du script.
    *
    * @param int[optional] $relTimeOutMinutes Timeout depuis la dernière page (1h défaut)
    * @param int[optional] $grandTimeOutMinutes Timeout depuis création de la session (6h défaut)
    * @throws Gb_Exception si impossible de créer répertoire pour le sessions
    * @return string texte de warning ou ""
    */
    public static function session_start($relTimeOutMinutes=60, $grandTimeOutMinutes=360)
    {
        self::$grandTimeOutMinutes=$grandTimeOutMinutes;
        session_name(Gb_Glue::getProjectName()."_PHPID");
        self::getSessionDir();
        session_start();
    
        $client=md5("U:".$_SERVER["HTTP_USER_AGENT"]." IP:". $_SERVER["REMOTE_ADDR"]);
    
        $sWarning="";
        
        $curSession=array();
        if (isset($_SESSION["Gb_Session"])) {
            $curSession=$_SESSION["Gb_Session"];
        }
        if (empty($curSession["client"]))       { $curSession["client"]="";       }
        if (empty($curSession["uniqId"]))       { $curSession["uniqId"]="";       }
        if (empty($curSession["grandTimeout"])) { $curSession["grandTimeout"]=0;  }
        if (empty($curSession["relTimeout"]))   { $curSession["relTimeout"]=0;    }
            
    
// j'enlève parce que ca engendre une dépendance sur Gb_Request
//        $uniqId=Gb_Request::getForm("uniqId");
//        if( strlen($uniqId) && $uniqId != $curSession["uniqId"] )
//        { // session hijacking ? Teste l'uniqId du formulaire (ou get)
//            $curSession=self::destroy();
//            $sWarning.="<b>Votre session n'est pas authentifiée";
//            $sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
//        }
//        elseif (  $curSession["client"]!=$client )

        $time=time();
        if (  $curSession["client"]!=$client )
        { // session hijacking ? Teste l'IP et l'user agent du client
            //Gb_Log::logNotice("Session uniqId={$curSession['uniqId']} destroyed because client {$curSession['client']} != {$client} ");
            $curSession=self::destroy();
            $sWarning.="<b>Votre adresse IP ou votre navigateur a changé depuis la dernière page demandée.";
            $sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
        }
        elseif( ($curSession["grandTimeout"] && $time>$curSession["grandTimeout"])
             ||  ($curSession["relTimeout"]   && $time>$curSession["relTimeout"]   )     )
        {
            //Gb_Log::logNotice("Session destroyed because $time > ({$curSession["grandTimeout"]} or {$curSession["relTimeout"]})");
            $curSession=self::destroy();
            $sWarning.="<b>Votre session a expiré";
            $sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
        }
    
        if (strlen($curSession["uniqId"])==0)
        { // premier appel de la session: initialisation  du client
            $curSession=self::destroy();
        }
        elseif (rand(1, 100)<=20)
        { // 20% de chance de regénérer l'ID de session
            //Gb_Log::logDebug("session_regenerate_id() uniqId={$curSession['uniqId']}");
            session_regenerate_id(true);
        }
    
        $curSession["relTimeout"]=$time+60*$relTimeOutMinutes;
        
        Gb_Glue::registerPlugin("Gb_Log", array(__CLASS__, "GbLogPlugin"));
        
        $_SESSION["Gb_Session"]=$curSession;
    
        //$gto=Gb_String::date_fr($curSession['grandTimeout']);
        //$rto=Gb_String::date_fr($curSession['relTimeout']);
        //Gb_Log::logDebug("Session is uniqId={$curSession['uniqId']} client={$curSession['client']} grandTimeout=$gto relTimeout=$rto}");

        return $sWarning;
    }
  
  
  
  
    public static function destroy()
    {
        session_regenerate_id(true);
        $client=md5("U:".$_SERVER["HTTP_USER_AGENT"]." IP:". $_SERVER["REMOTE_ADDR"]);
        $a='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $u=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)};
        $uniqId=$u;
        $time=time();
        $curSession=array();
        $curSession["uniqId"]=$uniqId;
        $curSession["client"]=$client;
        $curSession["grandTimeout"]=$time+60*self::$grandTimeOutMinutes;
        
        $_SESSION=array();
        $_SESSION["Gb_Session"]=$curSession;
        
        //$gto=Gb_String::date_fr($curSession['grandTimeout']);
        //Gb_Log::logInfo("Session created uniqId={$uniqId} client={$client} grandTimeout=$gto");
        return $curSession;
    }
    
    
    
    
    public static function GbLogPlugin()
    {
        $uniqId=self::getUniqId();
        $uniqId=str_pad($uniqId, 6);
        return $uniqId;
    }

    
    /**
     * Renvoie la valeur SESSION, sans slash ou default si elle n'est pas définie
     *
     * @param string $key valeur à chercher
     * @param mixed[optional] $value valeur à renvoyer si non trouvé
     * @return mixed $_SESSION[$key]
     */
    public static function get($key, $default=false)
    {
        if ( isset($_SESSION[$key]) ) {
            return $_SESSION[$key];
        } else {
            return $default;
        }
    }

    /**
     * Stocke une valeur dans SESSION
     *
     * @param string $key valeur à chercher
     * @param mixed $key valeur à chercher
     * @return mixed la valeur
     */
    public static function set($key, $value)
    {
        $_SESSION[$key]=$value;
        return $value;
    }

    public static function _unset($key)
    {
        unset($_SESSION[$key]);
    }

    public static function _isset($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function _empty($key)
    {
        return empty($_SESSION[$key]);
    }
    
}