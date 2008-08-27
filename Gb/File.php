<?php
/**
 * Gb_File
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Db.php");
require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."String.php");

Class Gb_File
{
    
    /**
     * @var Gb_Db
     */
    protected $_db;

    protected $_fileroot;
    protected $_category;
    protected $_tableName;
    
    /**
     * Constructeur Pour uploader les fichiers dans /var/lib/php5/files/c2i:
     *
     * @param string $fileroot "/var/lib/php5/files"
     * @param Gb_Db $db
     * @param string $tableName
     * @param string $category "c2i" si null, tous fichiers autorisés et upload désactivé
     * @param string[optional] $tmpdir "/var/lib/php5/files/tmp" si null alors upload désactivé
     * 
     * @throws Gb_Exception
     */
    public function __construct($fileroot, Gb_Db $db=null, $tableName="fichier", $category=null, $tmpdir=null)
    {
        if (strlen($category)==0) {
            $tmpdir=null;
        } else {
            $category=trim($category, DIRECTORY_SEPARATOR);
        }
        $this->_tableName=$tableName;

        // vérifie que les répertoires existent
        if (!is_dir($fileroot)) {
            throw new Gb_Exception("root $fileroot is not a dir !");
        }
        if (!is_writable($fileroot)) {
            throw new Gb_Exception("root $fileroot is not writable !");
        }
        if ($tmpdir!==null) {
            if ( !is_dir($tmpdir) ) {
                throw new Gb_Exception("tmp $tmpdir is not a dir !");
            }
            if ( !is_writable($tmpdir) ) {
                throw new Gb_Exception("tmp $tmpdir is not a writable !");
            }
        }
        
        $this->_db=$db;
        $this->_fileroot=$fileroot;
        $this->_tmpdir=$tmpdir;
        $this->_category=$category;
    }
    
    /**
     * Renvoie un fichier ou false si non existant
     *
     * @param integer $id
     * @return array|false
     */
    protected function _getFile($id)
    {
        $cat=$this->_category;
        $sql =" SELECT fic_code AS 'id', fic_categorie AS 'category', fic_nom AS 'nom', fic_taille AS 'length', fic_commentaire AS 'comment', fic_fs_dossier AS 'fs_folder', fic_fs_nom AS 'fs_name'";
        $sql.=" FROM".$this->_tableName;
        $sql.=" WHERE fic_code=?";
        if ($cat) {
            $sql.=" AND fic_categorie=?";
            $aFile=$this->_db->retrieve_one($sql, array($id, $cat));
        } else {
            $aFile=$this->_db->retrieve_one($sql, array($id));
        }
        if (!$aFile) {
            return false;
        }
        return $aFile;
    }
    
    
    public function getFsName($id)
    {
        $aFile=$this->_getFile($id);
        if (!$aFile) {
            return false;
        }
        
        $fsname="";
        $fsname.=$this->_fileroot.DIRECTORY_SEPARATOR;
        $fsname.=$aFile["category"].DIRECTORY_SEPARATOR;
        $fsname.=$aFile["fs_folder"].DIRECTORY_SEPARATOR;
        $fsname.=$aFile["fs_name"];

        return $fsname;
    }
    
    public function purgeTempDir()
    {
        /** @todo a implémenter*/
    }
    

    /**
     * Déplace un fichier uploadé dans dans tmpdir
     *
     * @param string $fname nom temporaire du fichier uploadé (source, donné par $_FILES[n]["tmp_name"])
     * @param string $prefix[optional] préfixe du nom de fichier utilisté
     * 
     * @return string nom complet du fichier
     * @throws Gb_Exception
     */
    public function storeUploadedTemporary($fname, $prefix="")
    {
        // trouve un nom de fichier
        $tmpfname=tempnam($this->_tmpdir, $prefix);  // construit un nom de fichier temporaire dans $tmpdir, et qui commence par $prefix

        $res=move_uploaded_file($fname, $tmpfname);
        if ($res===false) {
            throw new Gb_Exception("impossible de déplacer le fichier");
        }
     
        return $tmpfname;
    }
    
    
    /**
     * Enter description here...
     *
     * @param string $fname nom de fichier dans le filesystem
     * @param string $sourceFname nom de fichier original
     * @param string $targetFolder exemple "124/567" (toujours des / )
     * @param string $targetPrefix "toto"
     * @param string[optional] $comment
     * 
     * @return 
     * 
     * @throws Gb_Exception
     */
    public function store($fname, $sourceFname, $targetFolder, $targetPrefix, $comment=null)
    {
        $targetFolder=trim($targetFolder, DIRECTORY_SEPARATOR);
        
        if (!file_exists($fname) || !is_readable($fname)) {
            throw new Gb_Exception("Erreur fichier introuvable ($fname)");
        }
        
        // crée le répertoire destination
        $fsfolder=$this->_fileroot;
        foreach (explode("/", $targetFolder) as $folder) {
            $fsfolder.=DIRECTORY_SEPARATOR.$folder;
            if (!is_dir($fsfolder)) {
                if ( mkdir($fsfolder, 0770)!==true || !is_dir($fsfolder) || !is_writable($fsfolder)) {
                    throw new Gb_Exception("Impossible de créer $fsfolder");
                }
            }
        }
        
        // obtient la taille du fichier
        $length=filesize($fname);
        if ($length === false) {
            throw new Gb_Exception("Erreur filesize($fname)");
        }

        // trouve l'extension de $sourceFname
        $pos=strrpos($sourceFname, ".");
        if ($pos===false) {
            $nonExt=$sourceFname;
            $ext="";
        } else {
            $nonExt=substr($sourceFname, 0, $pos);
            $ext=".".$this->_sanitize(substr($sourceFname, $pos+1));
        }

        // obtient le numéro du fichier
        $fileid=$this->_db->sequenceNext($this->_tableName."_seq");
        
        $nonExt=$this->_sanitize($nonExt);
        $newfName=$targetPrefix."{".$fileid."}".$nonExt.$ext;
        
        // insertion du fichier dans la base de donnée --> récupère $fileid
        $this->_db->insert(
            $this->_tableName,
            array(
                "fic_id"        => $fileid,
                "fic_nom"       => $sourceFname,
                "fic_taille"    => $length,
                "fic_categorie" => $this->_category,
                "fic_fs_dossier"=> $targetFolder,
                "fic_fs_nom"    => $newfName,
                "fic_commentaire"=>$comment
            )
        );
        
        
        $newFullname=$fsfolder.DIRECTORY_SEPARATOR.$newfName;
        if (rename($fname, $newFullname)!== true) {
            throw new Gb_Exception("impossible de renommer $fname en $newFullname");
        }
        Gb_Log::logDebug("rename $fname to $newFullname");

        return $fileid;
    }
    
    
    
    protected function _sanitize($f)
    {
        $f=strtolower(Gb_String::mystrtoupper($f));
        $f2="";
        $len=strlen($f);

        for ($i=0; $i<$len; $i++) {
            $c=ord(substr($f, $i, 1));
            if (   ($c>=ord("a") && $c<=ord("z"))  ||   ($c>=ord("0") && $c<=ord("9"))   ) {
                $f2.=chr($c);
            }
        }

        return $f2;
    }
    /**
     * Renvoie la revision de la classe
     *
     * @return integer
     */
    public static function getRevision()
    {
        $revision='$Revision$';
        $revision=trim(substr($revision, strrpos($revision, ":")+2, -1));
        return $revision;
    }
    
}
