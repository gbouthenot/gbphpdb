<?php
/**
 * Class Gb_Timer
 *
 * @author Gilles Bouthenot
 * @version 1.00
 *
 */
/**
 * 
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Glue.php");
require_once(_GB_PATH."Log.php");

Class Gb_Timer
{
  protected $startime;
  protected $name;
  protected $pause=0;

  protected static $nbInstance_total=0;           // Nombre de classes ouvertes au total       
  protected static $nbInstance_peak=0;            // maximum ouvertes simultanément                 
  protected static $nbInstance_current=0;         // nom d'instances ouvertes en ce moment          

  protected static $fPluginRegistred=false;
  
  
  /**
   * Initialise le timer.
   *
   * @param string[optional] $name text à afficher dans footer
   */
  public function __construct($name='')
  {
    $this->startime=microtime(true);
    $this->name=$name;

    self::$nbInstance_total++;
    self::$nbInstance_current++;
    self::$nbInstance_peak=max(self::$nbInstance_peak, self::$nbInstance_current);
    
    if (!self::$fPluginRegistred)
    {
        Gb_Glue::registerPlugin("Gb_Response_Footer", array(__CLASS__, "GbResponsePlugin"));
        self::$fPluginRegistred=true;
    }
    
  }

  public function __destruct()
  {
    self::$nbInstance_current--;
  }

  /**
   * Réinitialise le timer. Annule une éventuelle pause
   */
  public function reset()
  {
    $this->startime=microtime(true);
    $this->pause=0;
  }

  /**
   * Renvoie le temps écoulé
   *
   * @param int|null $nbdigits nombre de chiffres significatifs|null pour inchangé
   * @return float temps écoulé
   */
  public function get($nbdigits=3)
  { if (empty($nbdigits))
      return $this->pause>0?$this->pause:microtime(true)-$this->startime;
    return $this->pause>0?Gb_Util::roundCeil($this->pause, $nbdigits):Gb_Util::roundCeil((microtime(true)-$this->startime), $nbdigits);
  }

  /**
   * loggue l'état du timer courant
   *
   * @param string[optional] $text
   */
  public function logTimer($level=Gb_Log::LOG_DEBUG, $text="")
  {
    if (!strlen($text))
    {
      if (strlen($this->name))
        $text=$this->name;
      else
        $text="Timer ".$this->intance;
    }
    $text.=": ".$this->get()." s";

    $vd=debug_backtrace();
    $vd0=$vd[0];
    $vd1=$vd[1];

    Gb_Log::writelog($level, $text, $vd0["file"], $vd0["line"], $vd1["function"], "...", null);
  }

  /**
   * Renvoie le temps écoulé avec 3 décimales
   *
   * @return string temps écoulé
   */
  public function __toString()
  {
    return (string) $this->get(3);
  }

  /**
   * Pause le timer. Les appels à get() sont figés, resume() pour reprendre
   */
  public function pause()
  {
    if ($this->pause==0)
      $this->pause=$this->get(null);
  }

  /**
   * Reprend le comptage après un pause()
   */
  public function resume()
  {
    if ($this->pause!=0)
      $this->startime=microtime(true)-$this->pause;
    $this->pause=0;
  }




    public static function GbResponsePlugin()
    {
      $ret="";
      
      $timetotal=self::$nbInstance_total;
      $timepeak=self::$nbInstance_peak;
      $ret.="Gb_Timer:{ total:$timetotal peak:$timepeak }";
      
      return $ret;
    }
  
}
