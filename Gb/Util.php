<?php
/**
 * 
 */

if (!defined("_GB_PATH")) {
	define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Log.php");

/**
 * class GbUtil
 *
 * @author Gilles Bouthenot
 * @version 1.01
 *	function str_readfile($filename) : à remplacer par file_get_contents($filename)
 *
 */
Class Gb_Util
{
	const Gb_UtilVERSION="2alpha";

	// ***********************
	// variables statiques accessibles depuis l'extérieur avec Gb_Util::$head et depuis la classe avec self::$head
	// ************************

	public static $footer="";				// Le footer
	public static $projectName="";			// Nom du projet
	public static $debug=0;                 // par défaut, pas de mode débug
	public static $show_footer=0;           // ne pas afficher le footer par défaut
	public static $nologo=0;                // ne pas afficher "built with gbpgpdb vxxx"
	public static $forbidDebug=0;           // ne pas interdire de passer en débug par $_GET["debug"]
	public static $preventGzip=0;           // compresse en gzip
	public static $noFooterEscape=0;		// Evite la ligne </div></span>, etc...

	public static $head=array(
		 "title"              =>""
		,"description"        =>array("name", "")
		,"keywords"           =>array("name", "")
		,"author"             =>array("name", "")
		,"copyright"          =>array("name", "")
		,"x-URL"              =>array("name", "")
		,"x-scriptVersion"    =>array("name", self::Gb_UtilVERSION)
		,"Expires"            =>array("http-equiv", "")                                  // mettre à vide pour une date du passé
		,"Content-Type"       =>array("http-equiv", "text/html;  charset=ISO-8859-15")
		,"Content-Script-Type"=>array("http-equiv", "text/javascript")
		,"Content-Style-Type" =>array("http-equiv", "text/css")
		,"Content-Language"   =>array("http-equiv", "fr")
		,"doctype"            =>"<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>"
		,"head_area"          =>""
		,"head_files"         =>array()
		,"body_tag"           =>"<body>"
		,"body_header"        =>""
		,"body_header_files"  =>array()
		);

	public static $logFilename="";					// Fichier de log, par défaut error_log/PROJECTNAME.log
	public static $sessionDir="";						// Répertoire des sessions par défaut session_path/PROJECTNAME/sessions
	public static $cacheDir="";							// Répertoire du cache par défaut session_path/PROJECTNAME/cache

	// pour send_headers()
	const P_HTTP=0;										// headers HTTP
	const P_CUSTOM=1;									// autre (pour gestion complete)
	const P_HTML=2;										// dans la balise <HTML>
	const P_HEAD=3;										// dans la balise <HEAD>
	const P_XHEAD=4;									// après la balise </HEAD>
	const P_BODY=5;										// dans la balise <BODY>
	const P_XBODY=6;									// après la balise </BODY>
	const P_XHTML=7;									// après la balise </HTML>
	public static $html_parse=self::P_HTTP;

	// " et $ ignorés
	const STR_SRC=  "' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~€?‚ƒ„…†‡ˆ‰Š‹Œ?Ž??‘’“”•–—˜™š›œ?žŸ ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ";
	const STR_UPPER="' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`ABCDEFGHIJKLMNOPQRSTUVWXYZ{|}~€?,F,_†‡ˆ%S‹O?Z??''“”.--˜TS›O?ZY IC£¤¥|§¨C2<¬­R¯°±23'UQ.¸10>¼½¾?AAAAAAACEEEEIIIIDNOOOOOXOUUUUYþBAAAAAAACEEEEIIIIONOOOOO/OUUUUYþ";


	// *****************
	// Variables privées
	// *****************


	private static $starttime=0;


	/**
	 * Cette classe ne doit pas être instancée !
	 */
	private function __construct()
	{
	}
	

	/**
	 * Initialise gzip, error_reporting, APPELLE main(), affiche le footer et quitte
	 *
	 * compresse en gzip
	 * Met error_reporting si debug, ou bien si _GET["debug"] (sauf si forbidDebug)
	 * Appelle main()
	 * Si débug (ou showFooter), affiche le footer
	 *
	 * @param string[optional] $function fonction à appeler (main si non précisé)
	 */
	public static function startup($function="main", $param=array())
	{
		self::$starttime=microtime(true);

		if (self::$preventGzip==0)
			ob_start("ob_gzhandler");

		error_reporting(E_ERROR);
		if ( self::$debug || (self::getFormGet("debug") &&	!self::$forbidDebug) )
		{
			error_reporting( E_ALL | E_STRICT );
			self::$debug=1;
		}
		else
			self::$debug=0;

		if (is_array($function) || function_exists($function))
			Gb_Log::log_function(self::LOG_DEBUG, "", $function, $param);
		else
			throw new Gb_Exception("function main() does not exist !");

		// Affichage du footer
		if (self::$debug || self::$show_footer) {
			$totaltime=microtime(true)-self::$starttime;

			self::$footer=htmlspecialchars(self::$footer, ENT_QUOTES);
			self::$footer.=sprintf("Total time: %s s ", self::roundCeil($totaltime));

			if (class_exists("Gb_Db")) {
				$sqltime=Gb_Db::get_sqlTime();
				$dbpeak=Gb_Db::get_nbInstance_peak();
				$dbtotal=Gb_Db::get_nbInstance_total();
				if ($sqltime>0) {
					$sqlpercent=$sqltime*100/$totaltime;
					self::$footer.=sprintf("(%.2f%% sql) ", $sqlpercent);
				}
				if ($dbtotal>0) {
					self::$footer.="Gb_Db:{total:$dbtotal peak:$dbpeak} ";
				}
				
			}

			if ( class_exists("Gb_Timer") ) {
				$timetotal=Gb_Timer::get_nbInstance_total();
				if ($timetotal) {
					$timepeak=Gb_Timer::get_nbInstance_peak();
					self::$footer.="Gb_Timer:{total:$timetotal peak:$timepeak} ";
				}
			}
				
			self::$footer.="\n";

			if (!self::$noFooterEscape)
				echo "</span></span></span></div></div></div></div></div></p>";
			printf("\n<div class='Gb_Util_footer'>\n%s</div>\n", self::$footer);
		}	// Affichage du footer

		$hp=self::$html_parse;
		if ($hp>=self::P_HTML && !self::$nologo)
			printf("<!-- built with Gb_Util v%s -->\n", self::Gb_UtilVERSION);
		elseif (!self::$nologo)
			printf("built with Gb_Util v%s\n", self::Gb_UtilVERSION);

		if ($hp>=self::P_BODY && $hp<self::P_XBODY)
			print "</body>\n";
		if ($hp>=self::P_HTML && $hp<self::P_XHTML)
			print "</html>\n";

		self::$html_parse=self::P_XHTML;
		exit(0);
	}


	/**
	 * Combine deux arrays
	 * Idem que array_merge, mais sans renuméroter les clés si clé numérique
	 *
	 * @param array $arr1
	 * @param array $arr2
	 * @return array
	 */
	public static function array_merge(array $arr1, array $arr2)
	{
		// si arr2 est plus grand, échange arr1 et arr2 pour itérer sur le plus petit
		if (count($arr2)>count($arr1)) {
			list($arr1, $arr2)=array($arr2, $arr1);
		}
		foreach ($arr2 as $k=>$v)
			$arr1[$k]=$v;
		return $arr1;
	}








	/**
	 * renvoie dans outTime l'heure (nombre de secondes depuis 01/01/1970)
	 * $sTime doit être formaté en "jj/mm/aaaa hh:mm:ss[.xxx]" ou "aaaa-mm-jj hh:mm:ss[.xxxxxx]"
	 *
	 * @param string_type $sTime
	 * @return integer
	 * @throws Gb_Exception
	 */
	public static function str_to_time($sTime)
	{
		$sTime=self::date_fr($sTime);
		if (strlen($sTime)==23)
			$sTime=substr($sTime,0,19);
		if (strlen($sTime)>=26)
			$sTime=substr($sTime,0,19);
		if (strlen($sTime)!=19)
		    throw new Gb_Exception("Error: bad time string:".$sTime);

		$aCTime1=array();
		$aCTimeDate=array();
		$aCTimeTime=array();
		$aCTime1=explode(' ', $sTime);
		$aCTimeDate=explode('/', $aCTime1[0]);
		$aCTimeTime=explode(':', $aCTime1[1]);
		$outTime=mktime($aCTimeTime[0], $aCTimeTime[1], $aCTimeTime[2], $aCTimeDate[1], $aCTimeDate[0], $aCTimeDate[2]);
		return $outTime;
	}


	
	// renvoie une chaine composée des premiers mots seulement de la chaine donnée
	// on donne une longueur minimal a la chaine avant laquelle on ne coupe pas.
	public static function create_nom($prenoms, $lmin=4)
	{	trim($prenoms);
		$out="";
		for ($i=0; $i<strlen($prenoms); $i++)
		{	$c=substr($prenoms,$i,1);
			if ($c==" " && $i>=$lmin)			break;
			$out.=$c;
		}
		return $out;
	}


	
	
	public static function send_headers($fPrint=1)
	{
		$head=self::$head;

		$ret="";
		$glo_parse=self::$html_parse;

		if ($glo_parse!=self::P_CUSTOM)
		{
			if ($glo_parse<self::P_HTML)
			{
				if (strlen($head["Expires"][1])==0) {             header("Cache-Control: no-cache, must-revalidate"); 		// HTTP/1.1
				                                                  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 			// Date du passé
				}

				if (strlen($head["Content-Type"][1]))             header("Content-Type: ".$head["Content-Type"][1]);
				else                                              header("Content-Type: text/html; charset=iso-8859-1");

				if (strlen($head["doctype"]))                     $ret.=$head["doctype"]."\n";
				else                                              $ret.="<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n";

				if (strlen($head["Content-Language"][1]))         $ret.="<html lang='".$head["Content-Language"][1]."'>\n";
				else                                              $ret.="<html>\n";
			}

			if ($glo_parse<self::P_HEAD)
				$ret.="<head>\n";

			if ($glo_parse<self::P_XHEAD)
			{
				foreach ($head as $key=>$val)
				{	switch (strtolower($key))
					{	       case "title":         $ret.="<title>".$val."</title>\n";
						break; case "head_area":     $ret.=$val;
						break; case "head_files":    foreach($val as $file)
						                             {if (file_exists($file) && is_readable($file)) $ret.=file_get_contents($file);}
						break; default:              if (is_array($val) && isset($val[1]) && (strtolower($val[0])=="name" || strtolower($val[0])=="http-equiv") && strlen($val[1]))
						                             {$ret.="<meta ".$val[0]."='".$key."' ".'content="'.htmlspecialchars($val[1], ENT_COMPAT).'" />'."\n";}
					}//switch
				}//foreach

				if (strlen($head["Expires"][1])==0) $ret.="<meta http-equiv='Expires' content='Thu, 15 Feb 1990 00:00:01 GMT' />\n";

				$ret.="</head>\n";
			}

			if ($glo_parse<self::P_BODY)
			{
				if (strlen($head["body_tag"]))         $ret.=$head["body_tag"];
				else                                   $ret.="<body>";
				if (isset($head["body_header"]))       $ret.=$head["body_header"];
				if (isset($head["body_header_files"])) foreach ($head["body_header_files"] as $file)
				                                       {if (file_exists($file) && is_readable($file)) $ret.=file_get_contents($file);}
/*
				if (isset($head["body_footer_files"])) foreach ($head["body_footer_files"] as $file)
				                                       {if (file_exists($file) && is_readable($file)) $ret.=file_get_contents($file);}
				if (isset($head["body_footer"])        $ret.=$head["body_footer"];
*/
			}

			self::$html_parse=self::P_BODY;
		}
		if ($fPrint)
			echo $ret;

		return $ret;
	} // function send_headers


    
	/**
	 * Inclut et execute un fichier et retourne le résultat dans une chaine
	 *
	 * @param string $file fichier à include
	 * @return string le fichier
	 * @throws Gb_Exception
	 */
	public static function include_file($file)
	{	// fait include d'un fichier, mais retourne le résultat dans une chaine
		// cela permet d'inclure un fichier et de l'éxecuter en même temps
		if (file_exists($file) && is_readable($file)) {
			ob_start();
			include($file);
			return	ob_get_clean();
		} else {
			throw new Gb_Exception("Fichier $file inaccessible");
		}
	}

	/**
	 * renvoie ?debug=1 ou &debug=1 si debug activé
	 *
	 * @param string $c caractère à renvoyer ("&" (par défaut) ou "?")
	 * @return string
	 */
	public static function url_debug($c="&")
	{
		if (self::$debug)
			return $c."debug=".self::$debug;
		return "";
	}

	/**
	 * conversion en majuscule, enlève les accents
	 *
	 * @param string $s
	 * @return string
	 */
	public static function mystrtoupper($s)
	{
		return strtr($s, self::STR_SRC, self::STR_UPPER);
	}

	/**
	 * converti, si nécessaire une date au format YYYY-MM-DD en DD/MM/YYYY
	 *
	 * @param string|"" $d date à convertir ou "" si date courante
	 * @return string
	 */
	public static function date_fr($d="")
	{
	  if (strlen($d)==0)
	    return date("d/m/Y H:i:s");
		if (substr($d,4,1)=='-')
		{	// date au format YYYY-MM-DD
			list($y,$m,$d)=split("-",$d);
			$d=substr($d,0,2).'/'.$m.'/'.$y.substr($d,2);
		}
		return $d;
	}

	/**
	 * converti, si nécessaire une date au format DD/MM/YYYY en YYYY-MM-DD
	 *
	 * @param string|"" $d date à convertir ou "" si date courante
	 * @return string
	 */
	public static function date_iso($d="")
	{
	  if (strlen($d)==0)
	    return date("Y-m-d H:i:s");
		if (substr($d,5,1)=='/')
		{	// date au format DD/MM/YYYY
			list($d,$m,$y)=split('/',$d);
			$d=substr($y,0,4).'-'.$m.'-'.$d.substr($y,4);
		}
		return $d;
	}

	

	private static function gpcStripSlashesArray(&$input, $key)
	{
		$key=stripslashes($key);
		$input=stripslashes($input);
	}

	/**
	 * Enlève les slashes des données GET, POST, COOKIE (gpc), si magic_quote_gpc est actif
	 *
	 * @param string $str chaïne à traiter
	 * @return string	$str avec éventuellement stripslashes
	 */
    public static function gpcStripSlashes($str)
	{
	  if (get_magic_quotes_gpc())
		if (is_array($str))
		{
			array_walk_recursive($str, array(__CLASS__, "gpcStripSlashesArray"));
			return $str;
		}
		else
		    return stripslashes($str);
	  return $str;
	}

	/**
	 * Renvoie la valeur POST, sans slash ou false si elle n'est pas définie
	 *
	 * @param string $index valeur à chercher
	 * @return string|false $_POST[$index]
	 */
	public static function getFormPost($index)
	{
		if (isset($_POST[$index]))
			return self::gpcStripSlashes($_POST[$index]);
		else
			return false;
	}

	/**
	 * Renvoie la valeur GET, sans slash ou false si elle n'est pas définie
	 *
	 * @param string $index valeur à chercher
	 * @return string|false $_GET[$index]
	 */
	public static function getFormGet($index)
	{
		if (isset($_GET[$index]))
			return self::gpcStripSlashes($_GET[$index]);
		else
			return false;
	}

	/**
	 * Renvoie la valeur POST, sans slash ou la valeur GET ou false si elles ne sont par définies
	 *
	 * @param string $index valeur à chercher
	 * @return string|false $_POST/$_GET[$index]
	 */
	public static function getForm($index)
	{
		if (isset($_POST[$index]))
			return self::gpcStripSlashes($_POST[$index]);
		elseif (isset($_GET[$index]))
			return self::gpcStripSlashes($_GET[$index]);
		else
			return false;
	}

	public static function roundCeil($num, $nbdigits=3)
	{
		if (empty($nbdigits))
			return $num;

//		$mul=pow(10,$nbdigits);
		$div=0;
		$num2=0;
		if ($num>.1)while($num>1) {$num/=10; $div++;}
		else        while($num<.1){$num*=10; $div--;}
		do {
			$num*=10;
			$nbdigits--;
			if ($nbdigits==0) $num+=0.999999;
			$digit=intval($num);
			$num-=$digit;
			$num2=$num2*10+$digit;
			$div--;
		}	while ($nbdigits>0);

		$num2*=pow(10, $div);
		return $num2;
	}






	/**
	 * Renvoit le nom du repertoire de cache
	 * crée le répertoire si besoin
	 *
	 * @return string cacheDir
	 */
	public static function getCacheDir()
	{
		$cacheDir=self::$cacheDir;

		if ($cacheDir=="")
		{
			$updir=session_save_path();
			$updir2=$updir.DIRECTORY_SEPARATOR.self::getProjectName();
			if ((!is_dir($updir2) || !is_writable($updir2)) && is_dir($updir) && is_writable($updir))
				@mkdir($updir2, 0700);
			$updir3=$updir2.DIRECTORY_SEPARATOR."cache";
			if ((!is_dir($updir3) || !is_writable($updir3)) && is_dir($updir2) && is_writable($updir2))
				@mkdir($updir3, 0700);

			if (!is_dir($updir3) || !is_writable($updir3))
				throw new Gb_Exception("Impossible de créer le répertoire $updir3 pour stocker le cache !");
		}
		return $cacheDir;
	}




	/**
	 * Renvoit le nom du repertoire de la session
	 * crée le répertoire si besoin
	 *
	 * @return string sessionDir
	 */
	public static function getSessionDir()
	{
		$sessionDir=self::$sessionDir;

		if ($sessionDir=="")
		{
			$updir=session_save_path();
			$updir2=$updir.DIRECTORY_SEPARATOR.self::getProjectName();
			if ((!is_dir($updir2) || !is_writable($updir2)) && is_dir($updir) && is_writable($updir))
				@mkdir($updir2, 0700);
			$updir3=$updir2.DIRECTORY_SEPARATOR."sessions";
			if ((!is_dir($updir3) || !is_writable($updir3)) && is_dir($updir2) && is_writable($updir2))
				@mkdir($updir3, 0700);
			if (is_dir($updir3) && is_writable($updir3))
				session_save_path($updir3);
			else throw new Gb_Exception("Impossible de créer le répertoire $updir3 pour stocker les sessions ! session_save_path()=$updir");
		}
		return $sessionDir;
	}


	/**
	 * Renvoit le nom du fichier de log
	 *
	 * @return string logFilename
	 */
	public static function getLogFilename()
	{
		$logFilename=self::$logFilename;
		if ($logFilename=="")
		{	// met le logFilename sur error_log/{PROJECTNAME}.LOG
			$logFilename=ini_get("error_log");
			$d=DIRECTORY_SEPARATOR;
			// 1: /var/log/php5 2:php_error.log
			unset($matches);
			preg_match("@^(.+$d)(.+)\$@", $logFilename, $matches);
			$logFilename=$matches[1].self::getProjectName().".log";

			self::$logFilename=$logFilename;
		}
		return $logFilename;
	}


	/**
	 * Renvoit le nom du projet, par défaut le répertoire du script php
	 *
	 * @return string projectName
	 */
	public static function getProjectName()
	{
		$sProjectName=self::$projectName;
		if ($sProjectName=="")
		{	// Met le nom du projet sur le nom du répertoire contenant le script
			// "/gbo/gestion_e_mvc/bootstrap.php" --> "__gbo__gestion_e_mvc__bootstrap.php"
			$sProjectName=__CLASS__;                          // par défaut, nom de la classe
			$d=DIRECTORY_SEPARATOR;
			$php_self=$_SERVER["PHP_SELF"];
			// 1: [////]   2: le répertoire    3: /    4:nomfich.php[/]
			unset($matches);
			preg_match("@^($d*)(.*)($d+)(.+$d*)$@", $php_self, $matches);
			if (isset($matches[2]) && strlen($matches[2]))
				$sProjectName=str_replace($d, "__", $matches[2]);
			self::$projectName=$sProjectName;
		}
		return $sProjectName;
	}



	/**
	 * Démarre une session sécurisée (id changeant, watch ip et l'user agent)
	 * Mettre echo Gb_Util::session_start() au début du script.
	 *
	 * @param int[optional] $relTimeOutMinutes Timeout depuis la dernière page (1h défaut)
	 * @param int[optional] $grandTimeOutMinutes Timeout depuis création de la session (6h défaut)
	 * @throws Gb_Exception si impossible de créer répertoire pour le sessions
	 * @return string	texte de warning ou ""
	 */
	public static function session_start($relTimeOutMinutes=60, $grandTimeOutMinutes=360)
	{
		session_name(self::getProjectName()."_PHPID");
		self::getSessionDir();
		session_start();

		$client=md5("U:".$_SERVER["HTTP_USER_AGENT"]." IP:". $_SERVER["REMOTE_ADDR"]);

		$sVarName=__CLASS__."_client";
		$sVarNameUniqId=__CLASS__."_uniqId";
		$sVarNameGrandTimeout=__CLASS__."_grandTimeout";
		$sVarNameRelTimeout=__CLASS__."_relTimeout";

		$sWarning="";

		$uniqId="";
		if (isset($_SESSION[$sVarNameUniqId]))	$uniqId=$_SESSION[$sVarNameUniqId];
		$uniqId=self::getForm("uniqId");
		if ( isset($_SESSION[$sVarName]) && $_SESSION[$sVarName]!=$client )
		{ // session hijacking ? Teste l'IP et l'user agent du client
			$_SESSION=array();
			session_regenerate_id(true);
			$sWarning.="<b>Votre adresse IP ou votre navigateur a changé depuis la dernière page demandée.";
			$sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
		}
		elseif( strlen($uniqId) && isset($_SESSION[$sVarNameUniqId]) && $uniqId != $_SESSION[$sVarNameUniqId] )
		{ // session hijacking ? Teste l'uniqId du formulaire (ou get)
			$_SESSION=array();
			session_regenerate_id(true);
			$sWarning.="<b>Votre session n'est pas authentifiée";
			$sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
		}
		elseif( (isset($_SESSION[$sVarNameGrandTimeout]) && time()>$_SESSION[$sVarNameGrandTimeout])
		     || (isset($_SESSION[$sVarNameRelTimeout])   && time()>$_SESSION[$sVarNameRelTimeout])		 )
		{
			$_SESSION=array();
			session_regenerate_id(true);
			$sWarning.="<b>Votre session a expiré";
			$sWarning.=" Pour protéger votre confidentialité, veuillez vous réidentifier.</b><br />\n";
		}

		if (empty($_SESSION[$sVarName]))
		{ // premier appel de la session: initialisation  du client
			if (strlen($uniqId)==0)
			{ // génére un uniqId ni n'existe pas déjà, sinon reprend l'ancien
				$a='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
				$u=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)}; $u.=$a{mt_rand(0, 61)};
				$uniqId=$u;
			}
			$_SESSION[$sVarNameUniqId]=$uniqId;
			$_SESSION[$sVarName]=$client;
			$_SESSION[$sVarNameGrandTimeout]=time()+60*$grandTimeOutMinutes;
		}
		elseif (rand(1, 100)<=20)
		{	// 20% de chance de regénérer l'ID de session
			session_regenerate_id(true);
		}

		$_SESSION[$sVarNameRelTimeout]=time()+60*$relTimeOutMinutes;

		return $sWarning;
	}

}

