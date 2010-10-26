<?php
/**
 * Gb_Cache
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

Class Gb_Cache
{
    public static       $cacheDir="";                  // Répertoire du cache par défaut session_path/PROJECTNAME/cache
    protected static    $nbTotal=0;                    // Nombre d'objet au total
    protected static    $nbCacheHits=0;                // Nombre de cache hit
    protected static    $nbCacheMiss=0;                // Nombre de cache miss

    protected static    $fPluginRegistred=false;
    
    /**
     * Renvoie le nom du repertoire de cache
     * crée le répertoire si besoin
     *
     * @return string cacheDir
   * @throws Gb_Exception
     */
    public static function getCacheDir()
    {
        if ( self::$cacheDir=="" ) {
            $updir=Gb_Glue::getOldSessionDir();
            $updir2=$updir.DIRECTORY_SEPARATOR.Gb_Glue::getProjectName();
            if ( (!is_dir($updir2) || !is_writable($updir2)) && is_dir($updir) && is_writable($updir) )
                @mkdir($updir2, 0700);
            $updir3=$updir2.DIRECTORY_SEPARATOR."cache";
            if ( (!is_dir($updir3) || !is_writable($updir3)) && is_dir($updir2) && is_writable($updir2) )
                @mkdir($updir3, 0700);
            if ( !is_dir($updir3) || !is_writable($updir3) )
                throw new Gb_Exception("Impossible de créer le répertoire $updir3 pour stocker le cache !");
            self::$cacheDir=$updir3;
        }
        return self::$cacheDir;
    }

    public static function get_nbTotal()
    {
        return self::$nbTotal;
    }

    public static function get_nbCacheHits()
    {
        return self::$nbCacheHits;
    }
    
    public static function get_nbCacheMiss()
    {
        return self::$nbCacheMiss;
    }
    
    
    protected $cacheEngine;
    protected $cacheID;
    protected $fUpdated;
    protected $fExpired;
    protected $cacheHit;

    protected $values;
    
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
     * Crée un cacheableObject
     *
     * @param string $cacheID
     * @param integer|string|array[optional] $ttl durée de vie en secondes, ou fichier de référence. Ou array de durée et fichiers. Par défaut 10 secondes.
     * @param boolean $fActivated use cache (useful for debuging)
     */
    public function __construct($cacheID, $ttl=10, $fExpired=false, $fActivated=true)
    {
        require_once("Zend/Cache.php");

        $frontendOptions=array(
                'caching'=>$fActivated,               // true         Active/désactive le cache (utile pour debug)
                'logging'=>false,                     // false        Utilise Zend_Log
                'write_control'=>false,               // true         Vérifier l'écriture
                'automatic_serialization'=>true,      // false        Permet de sauvegarder pas uniquement des strings
                'automatic_cleaning_factor'=>10,      // 10           Probabilitée de 1/x de nettoyer le cache
        );

		$lifetime=null;
		$masterfiles=array();

		if (is_string($ttl)) {
            $masterfiles=array($ttl);  // string       Nom du fichier dont la date de modification servira de référence
        } elseif (is_array($ttl)) {
        	foreach ($ttl as $tt) {
        		if (is_string($tt)) {
        			$masterfiles[]=$tt;
        		} else {
        			$lifetime=$tt;
        		}
        	}
        } else {
            $lifetime=$ttl;
        }

        if (count($masterfiles)) {
            $frontend='File';
            $frontendOptions['master_files']=$masterfiles;
        } else {
        	$frontend='Core';
        }
        
        $frontendOptions['lifetime']=$lifetime;       // 3600         Durée de vie en secondes, null:validité permanente

        // garde uniquement les caractères [a-zA-Z0-9_]
        $n=strlen($cacheID);
        $cacheID2="";
        for ($i=0; $i<$n; $i++) { $c=$cacheID[$i]; $o=ord(strtoupper($c)); if ( ($o>=48 && $o<=57) || ($o>=65 && $o<=90) || $o==95 ) { $cacheID2.=$c; } }
        $cacheID=$cacheID2;
        
        $this->cacheEngine=Zend_Cache::factory(
            $frontend,                                // frontend: Core: par défaut, File: pour le mtime d'un fichier
            'File',                                   // backend: où le cache est stocké: File
            $frontendOptions,
            array(
                'cache_dir'=>self::getCacheDir(),     // '/tmp/'      Répertoire où stocker les fichiers
                'file_locking'=>true,                 // true         Utiliser file_locking
                'read_control'=>true,                 // true         Utilisation d'un checksum pour contrôler la validité des données
                'read_control_type'=>'crc32',         // 'crc32'      Type du checksum: crc32, md5 ou strlen
                'hashed_directory_level'=>0,          // 0            Profondeur de répertoire à utiliser
                'hashed_directory_umask'=>0700,       // 0700         umask à utiliser pour la structure de répertoires
                'file_name_prefix'=>'gb_cache',       // 'zend_cache' Préfixe ajouté aux fichiers. Attention, si vous enregistrez dans
                                                      //              un répertoire générique comme /tmp/, à ce que ce préfixe soit
                                                      //              spécifique à chaque application !
                )
        );
        
        $this->cacheID=$cacheID;
        if ($fExpired) {
            $this->values=array();
            $this->cacheHit=false;
        } else {
            $this->values=$this->cacheEngine->load($cacheID);
            if ( $this->values===false ) {
                // cache invalide ou expiré
                $this->values=array();
                $this->cacheHit=false;
            } else {
                $this->cacheHit=true;
            }
        }
        
        self::$nbTotal++;
        
        if (!self::$fPluginRegistred)
        {
            Gb_Glue::registerPlugin("Gb_Response_Footer", array(__CLASS__, "GbResponsePlugin"));
            self::$fPluginRegistred=true;
        }
        
    }

    /**
     * Sauve l'objet s'il a été modifié et qu'il n'est pas marqué comme expiré
     */
    public function __destruct()
    {
        if ($this->fUpdated && !$this->fExpired) {
            $this->cacheEngine->save($this->values, $this->cacheID);
        }
    }

    /**
     * Marque l'objet comme expiré, c'est à dire qu'au prochain appel, le cache ne sera pas utilisé
     */
    public function expire()
    {
        $this->cacheEngine->remove($this->cacheID);
        $this->fExpired=true;
    }


    public static function cacheHit()
    {
        return self::$cacheHit;
    }
    
    public static function cacheMiss()
    {
        return self::$nbCacheMiss;
    }
    

    /**
     * Renvoie un attribut
     *
     * @param string $index
     * @return mixed
     */
    public function __get($index)
    {
        if (isset($this->values[$index])) {
            return $this->values[$index];
        } else {
            return null;
        }
    }

    /**
     * Positionne un attribut
     *
     * @param string $index
     * @param mixed $newValue
     * @return mixed
     */
    public function __set($index, $newValue)
    {
        $this->values[$index]=$newValue;
        $this->fUpdated=true;
    }

    /**
     * Enlève un attribut
     *
     * @param string $index
     */
    public function __unset($index)
    {
        unset($this->values[$index]);
    }

    /**
     * fonction isset/empty
     *
     * @param string $index
     * @return boolean
     */
    public function __isset($index)
    {
        $res=isset($this->values[$index]);
        if ($res) { self::$nbCacheHits++; }
        else      { self::$nbCacheMiss++; }
        return $res;
    }
    
    
    
    
    public static function GbResponsePlugin()
    {
        $ret="";
      
        $nbtotal=self::$nbTotal;
        $nbcachehits=self::$nbCacheHits;
        $nbcachemiss=self::$nbCacheMiss;
        $ret.="Gb_Cache:{ total:$nbtotal hits:$nbcachehits miss:$nbcachemiss }";
        return $ret;
      }
    
}
