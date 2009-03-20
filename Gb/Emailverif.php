<?php
/**
 * Gb_Emailverif
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Db.php");
require_once(_GB_PATH."String.php");

Class Gb_Emailverif
{
    /*
     * CREATE TABLE `mail_confirm` (
     *   `mco_emailhash` CHAR(32) NOT NULL,
     *   `mco_date` DATETIME NOT NULL,
     *   `mco_ip` VARCHAR(50) NOT NULL,
     *   PRIMARY KEY (`mco_emailhash`)
     * ) ENGINE = InnoDB COMMENT = 'Stock les adresses mail validées par le web';
     */

    /**
     * @var Gb_Db
     */
    protected $_db;
    
    protected $_hoursValidity;
    protected $_salt;
    protected $_table;
    
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
     * Constructeur
     *
     * @param string $pass 32-hexadecimal chars
     * @param Gb_Db $db
     * @param integer[optional] $hoursValidy nombre d'heures (défaut 48) que la validation est considérée comme valide
     * @param string[optional] tablename (défaut mail_verif)
     */
    public function __construct($salt, Gb_Db $db, $hoursValidity=48, $table="mail_verif")
    {
        if (strlen($salt)!=32) { throw new Exception("Invalid salt"); }

        $this->_salt=$salt;
        $this->_db=$db;
        $this->_hoursValidity=$hoursValidity;
        $this->_table=$table;
    }

    public function emailToValidationCode($text)
    {
        $md5=$this->emailToHash($text);
        $md5=$this->myxor($md5, $this->_salt);
        $code="00";
        for ($i=0; $i<32; $i+=2) {
            $code=$this->myxor($code, substr($md5, $i, 2));
        }
        return $md5.$code;
    }
    
    
    /**
     * Vérifie si un mail a été validé
     *
     * @param string $email
     * @return boolean
     */
    public function checkEmail($email)
    {
        $hash=$this->emailToHash($email);
        $maxtime=time()-60*60*$this->_hoursValidity;
        $maxtime=Gb_String::date_iso($maxtime);
        $aRow=$this->_db->retrieve_one("SELECT 1 FROM mail_confirm WHERE mco_emailhash=? AND mco_date>?", array($hash, $maxtime));
        if ($aRow) { return true; } else { return false; }
    }
    
    /**
     * Enregistre un code de validation
     *
     * @param string $code (34 chars hexa)
     * @return boolean
     */
    public function submitValidationCode($code)
    {
        if (strlen($code)!=34) { return false; }
        
        $code=$this->decryptValidationCode($code);
        if ($code===false) { return false; }
        
        $security=$_SERVER['REMOTE_ADDR']." ".$_SERVER['HTTP_USER_AGENT'];
        $this->_db->delete("mail_confirm", array($this->_db->quoteInto("mco_emailhash=?", $code)));
        $this->_db->insert("mail_confirm", array("mco_emailhash"=>$code, "mco_date"=>new Zend_Db_Expr("NOW()"), "mco_ip"=>$security));
        return true;
    }
    
    
    
    protected function decryptValidationCode($text)
    {
        $code="00";
        for ($i=0; $i<32; $i+=2) {
            $code=$this->myxor($code, substr($text, $i, 2));
        }
        $emb=substr($text, 32, 2);
        if ($code === $emb) { return $this->myxor(substr($text, 0, 32), $this->_salt);}
        else                { return false;}
    }
    
    protected function emailToHash($email)
    {
        $email=Gb_String::mystrtoupper($email);
        return md5($email);
    }
    
    protected function myxor($a, $b)
    {
        if (strlen($a) != strlen($b)) { throw new Exception("xor: pas de meme taille"); }
        $ret="";
        for ($i=0,$count=strlen($a); $i<$count; $i++) {
            $c=$a[$i];
            $d=$b[$i];
            $e=hexdec($c) ^ hexdec($d);
            $ret.=dechex($e);
        }
        return $ret;
    }
}
