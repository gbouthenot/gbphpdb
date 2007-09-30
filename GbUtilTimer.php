<?php
/**
 * Class GbUtilTimer
 *
 * @author Gilles Bouthenot
 * @version 1.00
 *
 */
Class GbUtilTimer extends GbUtil
{
	private $startime;
	private $intance=0;
	private $name;
	private $pause=0;

	/**
	 * Initialise le timer.
	 *
	 * @param string[optional] $name text à afficher dans footer
	 */
	public function __construct($name='')
	{
		$this->intance=++GbUtil::$GbTimer_instance_max;
		$this->startime=microtime(true);
		$this->name=$name;
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
	{	if (empty($nbdigits))
			return $this->pause>0?$this->pause:microtime(true)-$this->startime;
		return $this->pause>0?GbUtil::roundCeil($this->pause, $nbdigits):GbUtil::roundCeil((microtime(true)-$this->startime), $nbdigits);
	}

	/**
	 * loggue l'état du timer courant
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
}
