<?php
/**
 * Gb_Form_Backend_GbModel
 *
 * @author Gilles Bouthenot
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Form2.php");
require_once(_GB_PATH."Form/Backend/Abstract.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Model.php");


class Gb_Form_Backend_GbModel extends Gb_Form_Backend_Abstract
{

    /**
     * @var \Gb\Model\Model $model
     */
    protected $model;


    /**
     * constructeur
     *
     * @param \Gb\Model\Model $model
     * @param array[optional] $aOptions options passées au constructeur de Gb_Form_Backend_Abstract
     */
    public function __construct(\Gb\Model\Model $model, $aParams=null)
    {
        $this->model = $model;
        parent::__construct($aParams);
    }

    /**
     * Remplit les valeurs depuis la base. Remplit hasData
     *
     * @param array $moreData array("col1", "col2")
     * @return boolean true, null si non applicable, false si pas d'info
     */
    public function getFromDb(array $moreData=array())
    {
        $moreData=array_merge(array_keys($this->moreData()), $moreData);
        $aDbCols=$moreData;

        // obient le nom des colonnes
        $aCols=$this->_parent->getDataAsArray();

        $fHasData=false;
        foreach (array_keys($aCols) as $nom) {
            $elem=$this->_parent->getElem($nom);
            $dbcol=$elem->backendCol();
            $aDbCols[]=str_replace("_DBCOL_", $dbcol, $this->dbColFormat());
        }

        //$sql=" SELECT ".implode(",", $aDbCols)." FROM ".$this->tableName." WHERE ".implode(" AND ", $this->where);
        //$aRes=$this->db->retrieve_one($sql);

        foreach (array_keys($aCols) as $nom) {
            $elem=$this->_parent->getElem($nom);
            $dbcol=$elem->backendCol();
            $dbcol = str_replace("_DBCOL_", $dbcol, $this->dbColFormat());
            $value = $this->model->$dbcol;
            $elem->backendValue($value);
        }
        $moreDataRead=array();
        foreach ($moreData as $nom) {
            $moreDataRead[$nom] = $this->model->$nom;
        }
        $this->_parent->moreDataRead($moreDataRead);
        $this->_parent->hasData(true);
        $fHasData=true;

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
        $aDbCols=array_merge($this->moreData(), $moreData);

        $aCols=$this->_parent->getDataAsArray();

        // find db column name and db column value
        foreach (array_keys($aCols) as $nom) {
            $elem=$this->_parent->getElem($nom);
            $dbcol=$elem->backendCol();
            $dbcol = str_replace("_DBCOL_", $dbcol, $this->dbColFormat());
            $this->model->$dbcol = $elem->backendValue();
        }


        try {
            //print_r($this->model->__toString());
            $this->model->save();
        } catch (Exception $e) {
            $e;
            Gb_Log::Log(Gb_Log::LOG_ERROR, "GBFORM->putInDb ERROR");
            return false;
        }

        Gb_Log::Log(Gb_Log::LOG_INFO, "GBFORMDATABASE->putInDb OK");
        return true;
    }



}
