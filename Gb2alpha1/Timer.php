<?php
/**
 * Class Gb_Timer
 *
 * @author Gilles Bouthenot
 * @version 1.00
 *
 */
Class Gb_Timer
{
	protected $startime;
	protected $name;
	protected $pause=0;

  protected static $nbInstance_total=0;           // Nombre de classes ouvertes au total       
  protected static $nbInstance_peak=0;            // maximum ouvertes simultan�ment                 
  protected static $nbInstance_current=0;         // nom d'instances ouvertes en ce moment          

	/**
	 * Initialise le timer.
	 *
	 * @param string[optional] $name text � afficher dans footer
	 */
	public function __construct($name='')
	{
		$this->startime=microtime(true);
		$this->name=$name;

    self::$nbInstance_total++;
    self::$nbInstance_current++;
    self::$nbInstance_peak=max(self::$nbInstance_peak, self::$nbInstance_current);
	}

	public function __destruct()
	{
		self::$nbInstance_current--;
	}

	public static function get_nbInstance_peak()
	{
		return self::$nbInstance_peak;
	}

	public static function get_nbInstance_total()
	{
		return self::$nbInstance_total;
	}

	/**
	 * R�initialise le timer. Annule une �ventuelle pause
	 */
	public function reset()
	{
		$this->startime=microtime(true);
		$this->pause=0;
	}

	/**
	 * Renvoie le temps �coul�
	 *
	 * @param int|null $nbdigits nombre de chiffres significatifs|null pour inchang�
	 * @return float temps �coul�
	 */
	public function get($nbdigits=3)
	{	if (empty($nbdigits))
			return $this->pause>0?$this->pause:microtime(true)-$this->startime;
		return $this->pause>0?GbUtil::roundCeil($this->pause, $nbdigits):GbUtil::roundCeil((microtime(true)-$this->startime), $nbdigits);
	}

	/**
	 * loggue l'�tat du timer courant
	 *
	 * @param string[optional] $text
	 */
	public function logTimer($level=GbUtil::LOG_DEBUG, $text="")
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

		GbUtil::writelog($level, $text, $vd0["file"], $vd0["line"], $vd1["function"], "...", null);
	}

	/**
	 * Renvoie le temps �coul� avec 3 d�cimales
	 *
	 * @return string temps �coul�
	 */
	public function __toString()
	{
		return (string) $this->get(3);
	}

	/**
	 * Pause le timer. Les appels � get() sont fig�s, resume() pour reprendre
	 */
	public function pause()
	{
		if ($this->pause==0)
			$this->pause=$this->get(null);
	}

	/**
	 * Reprend le comptage apr�s un pause()
	 */
	public function resume()
	{
		if ($this->pause!=0)
			$this->startime=microtime(true)-$this->pause;
		$this->pause=0;
	}
}