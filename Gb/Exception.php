<?php
/**
 * Gb_Exception
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

/**
 * Class Gb_Exception
 *
 * @author Gilles Bouthenot
 */
class Gb_Exception extends Exception
{
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
    
  public function __toString()
  {
    $message=__CLASS__ . ": \n";
    $trace=$this->getTrace();
    if (isset($trace[0])) {
      $file=$trace[0]["file"];
      $line=$trace[0]["line"];
      $function=$trace[0]["function"];
      $message.="Erreur dans $function(...): ".$this->getMessage()."\n";
      $message.="thrown in $file on line $line\n";
    } else {
      $message.=$this->getMessage();
    }
    return $message;
  }
}

