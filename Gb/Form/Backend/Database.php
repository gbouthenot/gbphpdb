<?php

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Form2.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Session.php");

/**
 * Gb_Form_Backend_Database
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Backend_Database extends Gb_Form_Backend_Abstract
{

    /**
     * @var Gb_Db $db
     */
    protected $db;
    protected $tableName;
    protected $where;
    
    /**
     * Renvoie la révision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
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
     * @param Gb_Db[optional] $db
     * @param string[optional] $tableName par défaut
     * @param array[optional] $where array($dbGE->quoteInto("vaf_usa_login=?", Auth::getLogin()))
     * @param array[optional] $aOptions options passées à Gb_Form2
     */
    public function __construct(Gb_Db $db=null, $tableName="", array $where=array(), $aParams=null)
    {
        $this->db=$db;
        $this->tableName=$tableName;
        $this->where=$where;
        parent::__construct($aParams);
    }
  
    /**
     * Remplit les valeurs depuis la base
     *
     * @param array $moreData array("col1", "col2")
     * @return boolean true, null si non applicable, false si pas d'info
     */
    public function getFromDb(array $moreData=array())
    {
        if (count($this->where)==0) {
            // no WHERE, we cannot find the line in the database
            return null;
        }
        
        $moreData=array_merge($moreData, array_keys($this->_parent->moreData()));
        $aDbCols=$moreData;
        
        // obient le nom des colonnes
        $aCols=$this->_parent->getDataAsArray();
        
        $fHasData=false;
        foreach (array_keys($aCols) as $nom) {
            $elem=$this->_parent->getElem($nom);
            $dbcol=$elem->backendCol();
            $aDbCols[]=$dbcol;
        }
        $sql=" SELECT ".implode(",", $aDbCols)." FROM ".$this->tableName." WHERE ".implode(" AND ", $this->where);
        $aRes=$this->db->retrieve_one($sql);
        
        if (is_array($aRes)) {
            foreach (array_keys($aCols) as $nom) {
                $elem=$this->_parent->getElem($nom);
                $dbcol=$elem->backendCol();
                $value=$aRes[$dbcol];
                $elem->backendValue($value);
            }
            $moreDataRead=array();
            foreach ($moreData as $nom) {
                $moreDataRead[$nom]=$aRes[$nom]; 
            }
            $this->_parent->moreDataRead($moreDataRead);
            $this->_parent->hasData(true);
            $fHasData=true;
        }    
        
        return $fHasData;
    }


  /**
   * Insère/update les valeurs dans la bdd
   *
   * @param array $moreData
   * @return boolean true si tout s'est bien passé
   */
    public function putInDb(array $moreData=array())
    {
        $aDbCols=array_merge($moreData, $this->_parent->moreData());

        $aCols=$this->_parent->getDataAsArray();

        if (strlen($this->tableName)==0 || count($aCols)==0) {
            return false;
        }

        // find db column name and db column value
        foreach (array_keys($aCols) as $nom) {
            $elem=$this->_parent->getElem($nom);
            $dbcol=$elem->backendCol();
            $aDbCols[$dbcol]=$elem->backendValue();
        }
        
        
        $db=$this->db;
        try {
            if (count($this->where)) {
                // il y a une condition where: fait un replace
                $db->replace($this->tableName, $aDbCols, $this->where);
            } else {
                // pas de where: fait insert
                $db->insert($this->tableName, $aDbCols);
            }
        } catch (Exception $e) {
            $e;
            Gb_Log::Log(Gb_Log::LOG_ERROR, "GBFORMDATABASE->putInDb ERROR table:{$this->tableName} where:".Gb_Log::Dump($this->where),$aDbCols );
            return false;
        }

        Gb_Log::Log(Gb_Log::LOG_INFO, "GBFORMDATABASE->putInDb OK table:{$this->tableName} where:".Gb_Log::Dump($this->where), $aDbCols );
        return true;
    }

    
    
}
