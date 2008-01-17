<?php
/**
 */

if (!defined("_GB_PATH")) {
	define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Util.php");

Class Gb_Cache
{
	public static $cacheDir=""; // Répertoire du cache par défaut session_path/PROJECTNAME/cache

    /**
     * Renvoit le nom du repertoire de cache
     * crée le répertoire si besoin
     *
     * @return string cacheDir
	 * @throws Gb_Exception
     */
    public static function getCacheDir()
    {
        $cacheDir=self::$cacheDir;
        if ( $cacheDir=="" ) {
            $updir=session_save_path();
            $updir2=$updir.DIRECTORY_SEPARATOR.Gb_Util::getProjectName();
            if ( (!is_dir($updir2)||!is_writable($updir2))&&is_dir($updir)&&is_writable($updir) )
                @mkdir($updir2, 0700);
            $updir3=$updir2.DIRECTORY_SEPARATOR."cache";
            if ( (!is_dir($updir3)||!is_writable($updir3))&&is_dir($updir2)&&is_writable($updir2) )
                @mkdir($updir3, 0700);
            if ( !is_dir($updir3)||!is_writable($updir3) )
                throw new Gb_Exception("Impossible de créer le répertoire $updir3 pour stocker le cache !");
            self::$cacheDir=$updir3;
        }
        return self::$cacheDir;
    }
	
}
