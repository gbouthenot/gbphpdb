<?php
/**
 */

if (!defined("_GB_PATH")) {
	define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Util.php");


class Gb_Log
{
    const LOG_EMERG=8;
    const LOG_ALERT=7;
    const LOG_CRIT=6;
    const LOG_ERROR=5;           // Ecriture bdd NOK    const LOG_WARNING=4;
    const LOG_NOTICE=3;          // Génération INE    const LOG_INFO=2;            // Ecriture bdd OK    const LOG_DEBUG=1;
    
    const LOG_NONE=9;
    const LOG_ALL=0;

	public static $logFilename="";		        			// Fichier de log, par défaut error_log/PROJECTNAME.log
    public static $loglevel_footer=self::LOG_DEBUG;
    public static $loglevel_file=self::LOG_WARNING;
    public static $loglevel_showuser=self::LOG_CRIT;

    protected static $aLevels=array(
        1=>"db--       ",
        2=>"nfo--      ",
        3=>"note--     ",
        4=>"warn---    ",
        5=>"error---   ",
        6=>"crit-----  ",
        7=>"alert----- ",
        8=>"emerg------"
    );


	/**
	 * Cette classe ne doit pas être instancée !
	 */
	private function __construct()
	{
	}


    /**
     * Renvoit le nom du fichier de log
     *
     * @return string logFilename
     */
    public static function getLogFilename()
    {
        $logFilename=self::$logFilename;
        if ( $logFilename=="" ) { // met le logFilename sur error_log/{PROJECTNAME}.LOG            $logFilename=ini_get("error_log");
            $d=DIRECTORY_SEPARATOR;
            // 1: /var/log/php5 2:php_error.log            unset($matches);
            preg_match("@^(.+$d)(.+)\$@", $logFilename, $matches);
            if (isset($matches[1])) { 
                $logFilename=$matches[1].Gb_Util::getProjectName().".log";
            } else {
                $logFilename=Gb_Util::getProjectName().".log";
            }
            self::$logFilename=$logFilename;
        }
        return $logFilename;
    }
	
	/**
	 * Loggue dans un fichier
	 *
	 * @param string $sText Message à ecrire
	 * @param string[optional] $sFName Fichier dans lequel ecrire, sinon self::getLogFilename
	 */
	public static function log_file($sText, $sFName="")
	{
		$REMOTE_USER="";          if (isset($_SERVER["REMOTE_USER"]))              $REMOTE_USER=         $_SERVER["REMOTE_USER"];
		$REMOTE_ADDR="";          if (isset($_SERVER["REMOTE_ADDR"]))              $REMOTE_ADDR=         $_SERVER["REMOTE_ADDR"];
		$HTTP_X_FORWARDED_FOR=""; if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))     $HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];

		if (!is_string($sText)) {
			$sText=self::dump($sText);
		}
		if (strlen($sFName)==0) {
			$sFName=self::getLogFilename();
		}
		if (strlen($sFName)) {
			$fd=fopen($sFName, "a");
			$sLog=date("dm His ");

			if (strlen($REMOTE_ADDR) || strlen($HTTP_X_FORWARDED_FOR)) {
				$sLog.=$REMOTE_ADDR;
				if (strlen($HTTP_X_FORWARDED_FOR)) {
					$sLog.="/".$HTTP_X_FORWARDED_FOR;
				}
				$sLog.=" ";
			}

			if (strlen($sLog)<40) {
				$sLog.=substr("                                                       ",0,40-strlen($sLog));
			}
			if (isset($_SESSION[__CLASS__."_uniqId"])) {
				$sLog.="uid=".$_SESSION[__CLASS__."_uniqId"]." ";
			}
			if (strlen($REMOTE_USER)) {
				$sLog.="user=".$REMOTE_USER." ";
			}
			$sLog.=$sText." ";

			$vd=debug_backtrace();
			if (isset($vd[1]))  $vd=$vd[1];
			else                $vd=$vd[0];

            $sLog.="file:".substr($vd["file"],-30)." line:".$vd["line"]." in ".$vd["function"];
            $sLog.=self::dump_array($vd["args"], "(%s)");
            $sLog.="\n";

			fwrite($fd, $sLog);
			fclose ($fd);
		}//endif (strlen($sFName))
	}


    /**
     * Loggue un message
     *
     * @param integer $level Gb_Log::LOG_DEBUG,INFO,NOTICE,WARNING,ERROR,CRIT,ALERT,EMERG
     * @param string $text message
     */
	public static function log($level=self::LOG_DEBUG, $text="")
	{
        $vd=debug_backtrace();
        $vd0=$vd1=$vd[0];
        if (isset($vd[1])) {
            $vd1=$vd[1];
        }
        self::writelog($level, $text, $vd0["file"], $vd0["line"], $vd1["function"], "...", null);
	}


    /**
     * Loggue une fonction
     *
     * @param integer $level Gb_Log::LOG_DEBUG,INFO,NOTICE,WARNING,ERROR,CRIT,ALERT,EMERG
     * @param string $text message
     * @param string|array $fName fonction ou array(classe,methode)
     * @param array[optional] $aParam paramètres
     * @return unknown
     */
	public static function log_function($level, $text, $fName, array $aParam=array())
	{
		$prevtime=microtime(true);

// récupère les arguments : NON: ca ne marche pas avec les références !
//		$aParam=array();
//		for ($i=1; $i<func_num_args(); $i++)
//			$aParam[]=func_get_arg($i);
//		$aParam=func_get_args();
//		array_shift($aParam);

		unset($sCallName);
		if (is_callable($fName, false, $sCallName))	// $sCallName reçoit le nom imprimable de la fonction, utile pour les objets
			$ret=call_user_func_array($fName, $aParam);
		else
			throw(new Gb_Exception("Fonction inexistante"));

		$time=microtime(true)-$prevtime;
		$sParam=substr(self::dump_array($aParam, "%s"), 0, 50);
		$sRet=substr(self::dump($ret), 0, 50);

		if (!strlen($text))
			$text="$sCallName()";
		$text.=" duration:".Gb_Util::roundCeil($time)." s";

		$vd=debug_backtrace();
		$vd0=$vd[0];

		self::writelog($level, $text, $vd0["file"], $vd0["line"], $sCallName, $sParam, $sRet);

		return $ret;
	}
	
	

    /**
     * Fonction privée, appelée par Gb_Log et Gb_Timer
     *
     * @param unknown_type $level
     * @param unknown_type $text
     * @param unknown_type $file
     * @param unknown_type $line
     * @param unknown_type $fxname
     * @param unknown_type $fxparam
     * @param unknown_type $fxreturn
     */
	public static function writelog($level, $text, $file, $line, $fxname="", $fxparam="", $fxreturn="")
	{
		$logFilename=self::getLogFilename();
		if (!is_string($text)) {
				$text=self::dump($text);
		}

		$sLevel=self::$aLevels[$level];
		$timecode=microtime(true)-Gb_Util::$starttime;
		$timecode=sprintf("%.03f", $timecode);
		$REMOTE_USER="";          if (isset($_SERVER["REMOTE_USER"]))		       $REMOTE_USER=         $_SERVER["REMOTE_USER"];
		$REMOTE_ADDR="";          if (isset($_SERVER["REMOTE_ADDR"]))		       $REMOTE_ADDR=         $_SERVER["REMOTE_ADDR"];
		$HTTP_X_FORWARDED_FOR=""; if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
		$date=date("dm His ");
		$file=substr($file,-30);

		if ($level>=self::$loglevel_showuser) {
		    // montre l'erreur
			echo $text;
		}

		if ($level>=self::$loglevel_file && strlen($logFilename)) {
		    // écrit dans fichier de log
			$sLog=$date;
			$sLog.=$REMOTE_ADDR;

			if (strlen($HTTP_X_FORWARDED_FOR))					$sLog.="/".$HTTP_X_FORWARDED_FOR;
			$sLog.=" ";

			if (strlen($sLog)<40)
				$sLog.=substr("                                                       ",0,40-strlen($sLog));

			$sLog.=$sLevel." ";

			if (isset($_SESSION[__CLASS__."_uniqId"]))
				$sLog.="uid=".$_SESSION[__CLASS__."_uniqId"]." ";

			if (strlen($REMOTE_USER))
				$sLog.="user=".$REMOTE_USER." ";

			$sLog.=$text." (";

			if (strlen($file))
				$sLog.=" file:$file line:$line";
			if (strlen($fxname))
				$sLog.=" in $fxname($fxparam) --> $fxreturn";
			$sLog.=" )\n";

			$fd = @fopen($logFilename, "a");
			if ($fd) {
				fwrite($fd, $sLog);
				fclose ($fd);
			}
		}

		if ($level>=self::$loglevel_footer) {
		     // écrit dans le footer
			$sLog="$sLevel t+$timecode: ";
			$sLog.=$text." (";
			if (strlen($file))
				$sLog.=" file:$file line:$line";
			if (strlen($fxname))
				$sLog.=" in $fxname($fxparam) --> $fxreturn";
			$sLog.=" )\n";
			Gb_Response::$footer.=$sLog;
		}

	}

	
	
	/**
	 * Renvoie une description sur une ligne d'une variable (comme print_r, mais sur une ligne)
	 * préférer dump
	 *
	 * @param mixed $var Variable à dumper
	 * @pram string[optional] $sFormat mettre "%s" au lieu de "array(%s)" par défaut
	 * @return string
	 */
	public static function dump_array(array $var, $sFormat="array(%s)")
	{
		$sLog="";
		$curnum=0;
		$fShowKey=false;
		foreach ($var as $num=>$arg)
		{	if ($curnum)			$sLog.=", ";
			$pr="";
			if (is_array($arg))
				$pr=self::dump_array($arg);
			else
			{
		    $pr=var_export($arg, true);
		    $pr=preg_replace("/^ +/m", "", $pr);                // enlève les espaces en début de ligne
		    $pr=preg_replace("/,\n\\)/m", ")", $pr);             // remplace les ,) par )
		    $pr=preg_replace("/,$/m", ", ", $pr);               // remplace "," par ", " en fin de ligne
		    $pr=str_replace("\n", "", $pr);                     // met tout sur une ligne
		    $pr=str_replace(" => ", "=>", $pr);                 // enlève les espaces avant et après "=>"
		    $pr=str_replace("array (", "array( ", $pr);         // formate array (
			}
			if ($fShowKey || $curnum!==$num)
			{
				$fShowKey=true;
				$pr="$num=>$pr";
			}
			$sLog.=$pr;
			$curnum++;
		}
		return sprintf($sFormat, $sLog);
	}

	/**
	 * Renvoie une description sur une ligne d'une variable (comme print_r, mais sur une ligne)
	 *
	 * @param mixed $var Variable à dumper
	 * @return string
	 */
	public static function dump($var)
	{
        if (is_array($var))
            return self::dump_array($var);
        $pr=var_export($var, true);
        $pr=preg_replace("/^ +/m", "", $pr);                // enlève les espaces en début de ligne
        $pr=preg_replace("/,\n\\)/m", ")", $pr);             // remplace les ,) par )
        $pr=preg_replace("/,$/m", ", ", $pr);               // remplace "," par ", " en fin de ligne
        $pr=str_replace("\n", "", $pr);                     // met tout sur une ligne
        $pr=str_replace(" => ", "=>", $pr);                 // enlève les espaces avant et après "=>"
        $pr=str_replace("array (", "array( ", $pr);         // formate array (
		return $pr;
	}
	
	
    
    
}
