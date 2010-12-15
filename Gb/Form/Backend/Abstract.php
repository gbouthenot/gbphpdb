<?php

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Form2.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Session.php");

/**
 * Gb_Form_Backend_Interface
 * 
 * @author Gilles Bouthenot
 * @version $Revision: 168 $
 * @Id $Id: Database.php 168 2010-10-26 09:29:58Z gbouthenot $
 */

abstract class Gb_Form_Backend_Abstract
{
    /**
     * @var Gb_Form2
     */
    protected $_parent;
    
    /**
     * @var array
     */
    protected $_aParams;
    
    /**
     * Link parent to Gb_Form2. Applies modificators specified with $aParams in the constructor.
     * @param Gb_Form2 $parent
     * @return Gb_Form_Backend_Abstract
     */
    public function setParent(Gb_Form2 $parent)
    {
        $this->_parent=$parent;
        foreach ($this->_aParams as $key=>$val) {
            call_user_func(array($this->_parent, $key), $val);
        }
        return $this;
    }
    

    public function __construct(array $aParams=null)
    {
        if (null === $aParams) {
            $aParams=array();
        }
        $this->_aParams=$aParams;
    }
    
    /**
     * Remplit les valeurs depuis la base
     *
     * @param array $moreData array("col1", "col2")
     * @return boolean true, null si non applicable, false si pas d'info
     */
    public abstract function getFromDb(array $moreData=array());


  /**
   * Insère/update les valeurs dans la bdd
   *
   * @param array $moreData
   * @return boolean true si tout s'est bien passé
   */
    public abstract function putInDb(array $moreData=array());

    
    
}
