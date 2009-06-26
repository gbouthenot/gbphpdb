<?php
/**
 * Gb_Form_Session
 * 
 * @author Gilles Bouthenot
 * @version $Revision: 125 $
 * @Id $Id: Form.php 125 2008-10-20 16:28:47Z gbouthenot $
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Form2.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Session.php");

Class Gb_Form_Database extends Gb_Form2
{

    /**
     * @var Gb_Db $db
     */
    protected $db;
    protected $tableName;
    protected $where;
    
    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que pr�cis�e, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision: 125 $';
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
     * @param string[optional] $tableName par d�faut
     * @param array[optional] $where array($dbGE->quoteInto("vaf_usa_login=?", Auth::getLogin()))
     * @param array[optional] $aOptions options pass�es � Gb_Form2
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
    * @return boolean true si donn�es trouv�es
    */
    public function getFromDb()
    {
        //todo: checkbox
        // obient le nom des colonnes
        $aCols=$this->getDataAsArray();
        
        $fData=false;
        $aDbCols=array();
        // non ! on doit r�cup�rer le nom de l'element et non dbcol /** @TODO **/
        foreach (array_keys($aCols) as $nom) {
            $elem=$this->getElem($nom);
            $dbcol=$elem->backendCol();
            $aDbCols[]=$dbcol;
        }
        $sql=" SELECT ".implode(",", $aDbCols)." FROM ".$this->tableName." WHERE ".implode(" AND ", $this->where);
        $aRes=$this->db->retrieve_one($sql);
        
        if (is_array($aRes)) {
            foreach (array_keys($aCols) as $nom) {
                $elem=$this->getElem($nom);
                $dbcol=$elem->backendCol();
                $value=$aRes[$dbcol];
                $elem->backendValue($value);
            }
            $this->hasData(true);
            $fData=true;
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

        if (strlen($this->tableName)==0 || count($aCols)==0) {
            return false;
        }

        $aDbCols=array();
        // non ! on doit r�cup�rer le nom de l'element et non dbcol /** @TODO **/
        foreach (array_keys($aCols) as $nom) {
            $elem=$this->getElem($nom);
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
