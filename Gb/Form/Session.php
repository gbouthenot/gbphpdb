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
require_once(_GB_PATH."Form.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Session.php");

Class Gb_Form_Session extends Gb_Form
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
     * @param string[optional] $sessionprefix
     */
    public function __construct($sessionprefix="")
    {
        $this->_sessionprefix=$sessionprefix;
        $this->fHasData=false;
    }

    public function addElement($nom, array $aParams)
    {
        if (!isset($aParams["dbCol"])) {
            $aParams["dbCol"]=$nom;
        }
        return parent::addElement($nom, $aParams);
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
    $aCols=array();
    foreach ($this->formElements as $nom=>$aElement) {
      if (isset($aElement["dbCol"]) && strlen($aElement["dbCol"])) {
        $aCols[$nom]=$aElement["dbCol"];
      }
    }
    
    $fData=false;
    
    // La requ�te a renvoy� une ligne
    foreach ($aCols as $nom=>$dbcol) {
        $aElement=$this->formElements[$nom];
        if (Gb_Session::_isset($this->_sessionprefix.$dbcol)) {
            // trouv� 
            $fData=true;
            $val=Gb_Session::get($this->_sessionprefix.$dbcol);
            // regarde si une fonction est fournie pour transformer avant de mettre dans la db 
            if (isset($aElement["fromDbFunc"])) {
                $func=$aElement["fromDbFunc"][0];
                $params=$aElement["fromDbFunc"][1];
                foreach ($params as &$param) {
                    if (is_string($param)) {
                        $param=sprintf($param, $val);
                    }
                }
                $val=call_user_func_array($func, $params);
            }
            
            $this->set($nom, $val);
            $this->fHasData=true;
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
        //@todo: radio, selectmultiple
        // obient le nom des colonnes
        $aCols=array();
        foreach ($this->formElements as $nom=>$aElement) {
            if (isset($aElement["dbCol"])) {
                $col=$aElement["dbCol"];
                $type=$aElement["type"];
                $val=$this->get($nom);
                if ($type=="CHECKBOX") {
                    $val= ($val) ? (1):(0);
                }
                // regarde si une fonction est fournie pour transformer avant de mettre dans la db 
                if (isset($aElement["toDbFunc"])) {
                    $func=$aElement["toDbFunc"][0];
                    $params=$aElement["toDbFunc"][1];
                    foreach ($params as &$param) {
                        if (is_string($param)) {
                            $param=sprintf($param, $val);
                        }
                    }
                    $val=call_user_func_array($func, $params);
                }

                Gb_Session::set($this->_sessionprefix.$col, $val);
            }
        }
    
        Gb_Log::Log(Gb_Log::LOG_INFO, "GBFORMSESSION->putInDb OK" );
        return true;
    }
    
    

}
