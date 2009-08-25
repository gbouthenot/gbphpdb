<?php

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Form2.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Session.php");

/**
 * Gb_Form_Session
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Session extends Gb_Form2
{
    protected $_sessionprefix;

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que pr�cis�e, ou Gb_Exception
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
     * constructeur
     *
     * @param string[optional] $sessionprefix
     * @param array[optional] $aOptions options pass�es � Gb_Form2
     */
    public function __construct($sessionprefix="", $aParams=null)
    {
        $this->_sessionprefix=$sessionprefix;
        parent::__construct($aParams);
    }

  
    /**
     * Remplit les valeurs depuis la session
     *
     * @return boolean true si donn�es trouv�es
     */
    public function getFromDb()
    {
        //todo: checkbox
        // obient le nom des colonnes
        $aCols=$this->getDataAsArray();
        
        $fData=false;
        // non ! on doit r�cup�rer le nom de l'element et non dbcol /** @TODO **/
        foreach (array_keys($aCols) as $nom) {
            $elem=$this->getElem($nom);
            $dbcol=$elem->backendCol();
            if (Gb_Session::_isset($this->_sessionprefix.$dbcol)) {
                // trouv� 
                $fData=true;
                $val=Gb_Session::get($this->_sessionprefix.$dbcol);
                $elem->backendValue($val);
                $this->hasData(true);
            }
        }
        
        return $fData;
    }


    /**
     * Ins�re/update les valeurs dans la bdd
     *
     * @param array $moreData
     * @return boolean true si tout s'est bien pass�
     */
    public function putInDb(array $moreData=array())
    {
        $aCols=$this->getDataAsArray($moreData);
    
        foreach ($aCols as $dbcol=>$val) {
            Gb_Session::set($this->_sessionprefix.$dbcol, $val);
        }
    
        Gb_Log::LogInfo("GBFORMSESSION->putInDb OK", $aCols );
        return true;
    }
    
}
