<?php
/**
 *
 */

require_once("GbForm.php");
require_once("GbDb.php");
require_once("GbTimer.php");


Class Gb
{
	protected static $footer="";
}



/**
 * class GbUtil
 *
 * @todo timeout session (est-ce ici ??)
 * @author Gilles Bouthenot
 * @version 1.01
 *	function str_readfile($filename) : à remplacer par file_get_contents($filename)
 *
 */
Class GbUtil extends Gb
{
	const GbUtilVERSION="2alpha";

	// pour log
	const LOG_NONE=9;
	const LOG_EMERG=8;
	const LOG_ALERT=7;
	const LOG_CRIT=6;
	const LOG_ERROR=5;						// Ecriture bdd NOK
	const LOG_WARNING=4;
	const LOG_NOTICE=3;
	const LOG_INFO=2;							// Ecriture bdd OK
	const LOG_DEBUG=1;
	const LOG_ALL=0;
	private static $aLevels=array(1=>"db--       ",
                                2=>"nfo--      ",
                                3=>"note--     ",
                                4=>"warn---    ",
                                5=>"error---   ",
                                6=>"crit-----  ",
                                7=>"alert----- ",
                                8=>"emerg------");
	public static $loglevel_footer  =self::LOG_DEBUG;
	public static $loglevel_file    =self::LOG_WARNING;
	public static $loglevel_showuser=self::LOG_CRIT;

	// ***********************
	// variables statiques accessibles depuis l'extérieur avec GbUtil::$head et depuis la classe avec self::$head
	// ************************

	public static $projectName="";					// Nom du projet
	public static $debug=0;                 // par défaut, pas de mode débug
	public static $show_footer=0;           // ne pas afficher le footer par défaut
	public static $nologo=0;                // ne pas afficher "built with gbpgpdb vxxx"
	public static $domParser=0;             // affiche PPK's DOMparse (http://www.quirksmode.org/dom/domparse.html)
	public static $forbidDebug=0;           // ne pas interdire de passer en débug par $_GET["debug"]
	public static $preventGzip=0;           // compresse en gzip
	public static $noFooterEscape=0;				// Evite la ligne </div></span>, etc...

	public static $head=array(
		 "title"              =>""
		,"description"        =>array("name", "")
		,"keywords"           =>array("name", "")
		,"author"             =>array("name", "")
		,"copyright"          =>array("name", "")
		,"x-URL"              =>array("name", "")
		,"x-scriptVersion"    =>array("name", self::GbUtilVERSION)
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
	const STR_SRC=  "' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~€?‚ƒ„…†‡ˆ‰Š‹Œ?Ž??‘’“”•–—˜™š›œ?žŸ ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ";
	const STR_UPPER="' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`ABCDEFGHIJKLMNOPQRSTUVWXYZ{|}~€?,F,_†‡ˆ%S‹O?Z??''“”.--˜TS›O?ZY IC£¤¥|§¨C2<¬­R¯°±23'UQ.¸10>¼½¾?AAAAAAACEEEEIIIIDNOOOOOXOUUUUYþBAAAAAAACEEEEIIIIONOOOOO/OUUUUYþ";


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
	 * Si débug (ou showFooter), affiche le footer (avec ppk dom parser si domParser)
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
			self::log_function(GbUtil::LOG_DEBUG, "", $function, $param);
		else
			throw new GbUtilException("function main() does not exist !");

		// Affichage du footer
		if (self::$debug || self::$show_footer)
		{		$totaltime=microtime(true)-self::$starttime;
				$sqltime=GbDb::get_sqlTime();
				$sqlpercent=$sqltime*100/$totaltime;
				self::$footer=htmlspecialchars(self::$footer, ENT_QUOTES);
				self::$footer.=sprintf("Total time: %s s (%.2f%% sql) ", GbUtil::roundCeil($totaltime), $sqlpercent);
				$timepeak=GbTimer::get_nbInstance_peak();
				$timetotal=GbTimer::get_nbInstance_total();
				if ($timetotal)
					self::$footer.="GbTimer:{total:$timetotal peak:$timepeak} ";

				$dbpeak=GbDb::get_nbInstance_peak();
				$dbtotal=GbDb::get_nbInstance_total();
				if ($dbtotal)
					self::$footer.="GbDb:{total:$dbtotal peak:$dbpeak} ";
				self::$footer.="\n";

				if (self::$debug && self::$domParser)
				{
	self::$footer.="
	<script language='javascript' type='text/javascript'>
	<!-- // PPK's DOMparse: http://www.quirksmode.org/dom/domparse.html
	var readroot,writeroot;var lvl = 1;var xtemp = new Array();var ytemp = new Array();var ztemp = new Array();var atemp = new Array();
	function ppkclearIt(){	if (!writeroot) return;
		while(writeroot.hasChildNodes()){writeroot.removeChild(writeroot.childNodes[0]);	}}
	function ppkinit(){
		formroot = document.forms['ppknodeform'];	read = formroot.ppkwrite.value;
		if (read && document.getElementById(read)) readroot = document.getElementById(read);	else readroot = document;
		writeroot = document.getElementById('ppknodemap');	ppkclearIt();
		tmp1 = document.createElement('P');	tmp2 = document.createTextNode('Content of ' + readroot.nodeName + ' with ID = ' + readroot.id);
		tmp1.appendChild(tmp2);	writeroot.appendChild(tmp1);	ppklevel();}
	function ppklevel(){
		atemp[lvl] = document.createElement('OL');
		for (var i=0;i<readroot.childNodes.length;i++)
		{	x = readroot.childNodes[i];
			if (x.nodeType == 3 && formroot.ppkhideempty.checked)
			{	var hide = true;
				for (j=0;j<x.nodeValue.length;j++){if (x.nodeValue.charAt(j) != '\\n' && x.nodeValue.charAt(j) != ' ')		{	hide = false;	break;}}
				if (hide) continue;}
			a1 = document.createElement('LI');		a2 = document.createElement('SPAN');
			if (x.nodeType == 3) a2.className='text';
			a3 = document.createTextNode(x.nodeName);		a2.appendChild(a3);		a1.appendChild(a2);		atemp[lvl].appendChild(a1);
			if (x.nodeType == 3 && formroot.ppkshowtext.checked)
			{a6 = document.createElement('BR');a5 = document.createTextNode(x.nodeValue);a2.appendChild(a6);a2.appendChild(a5);}
			if (x.attributes && formroot.ppkshowattr.checked)
			{	a3 = document.createElement('SPAN');			a3.className='attr';
				for (j=0;j<x.attributes.length;j++){	if (x.attributes[j].specified){a5 = document.createElement('BR');a6 = document.createTextNode(x.attributes[j].nodeName + ' = ' + x.attributes[j].nodeValue);a3.appendChild(a5);a3.appendChild(a6);}}
				a2.appendChild(a3);}
			if (x.hasChildNodes())
			{	lvl++;xtemp[lvl] = writeroot;ytemp[lvl] = readroot;ztemp[lvl] = i;
				readroot = readroot.childNodes[i];writeroot = atemp[lvl-1];ppklevel();
				i = ztemp[lvl];writeroot = xtemp[lvl];readroot = ytemp[lvl];lvl--;}}
		writeroot.appendChild(atemp[lvl]);}
	// -->
	</script>
	<div style='border:1px solid #000; background:#eee; margin:10px; padding:5px;'><form name='ppknodeform' style='display:inline;' action='' >
	<input style='width: 150px' name='ppkwrite' value='PPK dom parser ID'>
	<input type='checkbox' name='ppkshowtext' />show texts<input type='checkbox' name='ppkshowattr' />show attributes
	<input type='checkbox' name='ppkhideempty' checked='checked' />hide empty text nodes
	<input type='button' value='parse' onclick=\"ppkinit()\" />
	</form><div id='ppknodemap'></div></div>
	";
				} //debug

				if (!self::$noFooterEscape)
					echo "</span></span></span></div></div></div></div></div></p>";
				printf("\n<div class='GbUtil_footer'>\n%s</div>\n", self::$footer);
		}	// Affichage du footer

		$hp=self::$html_parse;
		if ($hp>=self::P_HTML && !self::$nologo)
			printf("<!-- built with GbUtil v%s -->\n", self::GbUtilVERSION);
		elseif (!self::$nologo)
			printf("built with GbUtil v%s\n", self::GbUtilVERSION);

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
		if (count($arr2)>count($arr1))
			list($arr1, $arr2)=array($arr2, $arr1);
		foreach ($arr2 as $k=>$v)
			$arr1[$k]=$v;
		return $arr1;
	}


	/**
	 * loggue dans un fichier
	 *
	 * @param string $sText Message à ecrire
	 * @param string[optional] $sFName Fichier dans lequel ecrire
	 * @todo errorlevel, filename
	 */
	public static function log_file($sText, $sFName="")
	{
		$REMOTE_USER="";          if (isset($_SERVER["REMOTE_USER"]))		       $REMOTE_USER=         $_SERVER["REMOTE_USER"];
		$REMOTE_ADDR="";          if (isset($_SERVER["REMOTE_ADDR"]))		       $REMOTE_ADDR=         $_SERVER["REMOTE_ADDR"];
		$HTTP_X_FORWARDED_FOR=""; if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];


		if (!is_string($sText))
			$text=self::dump($sText);

		if (strlen($sFName)==0)
			$sFName=self::getLogFilename();

		if (strlen($sFName))
		{
			$fd=fopen($sFName, "a");
			$sLog=date("dm His ");

			if (strlen($REMOTE_ADDR) || strlen($HTTP_X_FORWARDED_FOR))
			{
				$sLog.=$REMOTE_ADDR;
				if (strlen($HTTP_X_FORWARDED_FOR))
					$sLog.="/".$HTTP_X_FORWARDED_FOR;
				$sLog.=" ";
			}

			if (strlen($sLog)<40)
				$sLog.=substr("                                                       ",0,40-strlen($sLog));

			if (isset($_SESSION[__CLASS__."_uniqId"]))
				$sLog.="uid=".$_SESSION[__CLASS__."_uniqId"]." ";

			if (strlen($REMOTE_USER))
				$sLog.="user=".$REMOTE_USER." ";

			$sLog.=$sText." ";

			$vd=debug_backtrace();
			if (isset($vd[1]))  $vd=$vd[1];
			else                $vd=$vd[0];
			$sLog.="file:".substr($vd["file"],-30)." line:".$vd["line"]." in ".$vd["function"]."(";

			$sLog.=self::dump_array($vd["args"], "%s");

			$sLog.=")";

			$sLog.="\n";
			fwrite($fd, $sLog);
			fclose ($fd);
		}
	}


	public static function log($level=GbUtil::LOG_DEBUG, $text="")
	{
			$vd=debug_backtrace();
			$vd0=$vd1=$vd[0];
			if (isset($vd[1]))
				$vd1=$vd[1];

			self::writelog($level, $text, $vd0["file"], $vd0["line"], $vd1["function"], "...", null);
	}



	protected static function writelog($level, $text, $file, $line, $fxname="", $fxparam="", $fxreturn="")
	{
		$logFilename=self::getLogFilename();
			if (!is_string($text))
				$text=self::dump($text);

		$sLevel=GbUtil::$aLevels[$level];
		$timecode=microtime(true)-self::$starttime;
		$timecode=sprintf("%.03f", $timecode);
		$REMOTE_USER="";          if (isset($_SERVER["REMOTE_USER"]))		       $REMOTE_USER=         $_SERVER["REMOTE_USER"];
		$REMOTE_ADDR="";          if (isset($_SERVER["REMOTE_ADDR"]))		       $REMOTE_ADDR=         $_SERVER["REMOTE_ADDR"];
		$HTTP_X_FORWARDED_FOR=""; if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
		$date=date("dm His ");
		$file=substr($file,-30);

		if ($level>=self::$loglevel_showuser)
		{	// montre l'erreur
			echo $text;
		}
		if ($level>=self::$loglevel_file && strlen($logFilename))
		{	// ecrit dans fichier de log
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

			if ($fd=@fopen($logFilename, "a"))
			{
				fwrite($fd, $sLog);
				fclose ($fd);
			}
		}

		if ($level>=self::$loglevel_footer)
		{	// écrit dans le footer
			$sLog="$sLevel t+$timecode: ";
			$sLog.=$text." (";
			if (strlen($file))
				$sLog.=" file:$file line:$line";
			if (strlen($fxname))
				$sLog.=" in $fxname($fxparam) --> $fxreturn";
			$sLog.=" )\n";
			self::$footer.=$sLog;
		}

	}


	public static function dump_array($var, $sFormat="array(%s)")
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
		    $pr=preg_replace("/,\n\)/m", ")", $pr);             // remplace les ,) par )
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
    $pr=preg_replace("/,\n\)/m", ")", $pr);             // remplace les ,) par )
    $pr=preg_replace("/,$/m", ", ", $pr);               // remplace "," par ", " en fin de ligne
    $pr=str_replace("\n", "", $pr);                     // met tout sur une ligne
    $pr=str_replace(" => ", "=>", $pr);                 // enlève les espaces avant et après "=>"
    $pr=str_replace("array (", "array( ", $pr);         // formate array (
		return $pr;
	}



	// renvoie dans outTime l'heure qui correspond à la chaine $sTime.
	// (nombre de secondes depuis 01/01/1970)
	// $sTime doit être formaté en "jj/mm/aaaa hh:mm:ss" ou "jj/mm/aaaa hh:mm:ss.xxxxxx"
	// ou "aaaa-mm-jj hh:mm:ss" ou "aaaa-mm-jj hh:mm:ss.xxxxxx"

	public static function str_to_time($sTime, $outTime)
	{
		$sTime=self::date_fr($sTime);
		if (strlen($sTime)==23)
			$sTime=substr($sTime,0,19);
		if (strlen($sTime)>=26)
			$sTime=substr($sTime,0,19);
		if (strlen($sTime)!=19)
			return "Error: bad time string:".$sTime;

		$aCTime1=array();
		$aCTimeDate=array();
		$aCTimeTime=array();
		$aCTime1=explode(' ', $sTime);
		$aCTimeDate=explode('/', $aCTime1[0]);
		$aCTimeTime=explode(':', $aCTime1[1]);
		$outTime=mktime($aCTimeTime[0], $aCTimeTime[1], $aCTimeTime[2], $aCTimeDate[1], $aCTimeDate[0], $aCTimeDate[2]);
		return "OK";
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
		else
			return $ret;
	} // function send_headers


	/**
	 * fait include d'un fichier, mais retourne le résultat dans une chaine
	 * cela permet d'inclure un fichier et de l'éxecuter en même temps
	 *
	 * @param string $file fichier à include
	 * @return le fichier ou false
	 */
	public static function include_file($file)
	{	// fait include d'un fichier, mais retourne le résultat dans une chaine
		// cela permet d'inclure un fichier et de l'éxecuter en même temps
		if (file_exists($file) && is_readable($file)) {
			ob_start();
			include($file);
			return	ob_get_clean();
		} else {
			return false;
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
	 * convertit, si nécessaire une date au format YYYY-MM-DD en DD/MM/YYYY
	 *
	 * @param string|"" $d date à convertir ou "" si date courante
	 * @return unknown
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
	 * convertit, si nécessaire une date au format DD/MM/YYYY en YYYY-MM-DD
	 *
	 * @param string|"" $d date à convertir ou "" si date courante
	 * @return unknown
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
	 * Enlève les slashes des données GET, POST, COOKIE (gpc), si magic_quotes_gpc est actif
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

	public static function log_function($level, $text, $fName, $aParam)
	{
		$prevtime=microtime(true);

		// récupère les arguments : NON: ca ne marche pas avec les références !
//		$aParam=array();
//		for ($i=1; $i<func_num_args(); $i++)
//			$aParam[]=func_get_arg($i);
//		$aParam=func_get_args();
//		array_shift($aParam);

		if (is_callable($fName, false, &$sCallName))	// $sCallName reçoit le nom imprimable de la fonction, utile pour les objets
			$ret=call_user_func_array($fName, $aParam);
		else
			throw(new GbUtilException("Fonction inexistante"));

		$time=microtime(true)-$prevtime;
		$sParam=substr(self::dump_array($aParam, "%s"), 0, 50);
		$sRet=substr(self::dump($ret), 0, 50);

		if (!strlen($text))
			$text="$sCallName()";
		$text.=" duration:".GbUtil::roundCeil($time)." s";

		$vd=debug_backtrace();
		$vd0=$vd[0];

		GbUtil::writelog($level, $text, $vd0["file"], $vd0["line"], $sCallName, $sParam, $sRet);

		return $ret;
	}

	public static function roundCeil($num, $nbdigits=3)
	{
		if (empty($nbdigits))
			return $num;

		$mul=pow(10,$nbdigits);
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
		$sessionDir=self::$cacheDir;

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
				throw new GbUtilException("Impossible de créer le répertoire $updir3 pour stocker le cache !");
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
			else throw new GbUtilException("Impossible de créer le répertoire $updir3 pour stocker les sessions !");
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
			preg_match("@^($d*)(.*)($d+)(.+$d*)$@", $php_self, $matches);
			if (isset($matches[2]) && strlen($matches[2]))
				$sProjectName=str_replace($d, "__", $matches[2]);
			self::$projectName=$sProjectName;
		}
		return $sProjectName;
	}



	/**
	 * Démarre une session sécurisée (id changeant, watch ip et l'user agent)
	 * Mettre echo GbUtil::session_start() au début du script.
	 *
	 * @param int[optional] $relTimeOutMinutes Timeout depuis la dernière page (1h défaut)
	 * @param int[optional] $grandTimeOutMinutes Timeout depuis création de la session (6h défaut)
	 * @throws GbUtilException si impossible de créer répertoire pour le sessions
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
		$uniqIdForm=GbUtil::getForm("uniqId");
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














/**
 * Class GbUtilException
 *
 * @author Gilles Bouthenot
 * @version 1.00
 */
class GbUtilException extends Exception
{
	public function __toString()
	{
		//	$message=__CLASS__ . ": [{$this->code}]: {$this->message}";
		$message=__CLASS__ . ": \n";
		$trace=$this->getTrace();
		$file=$trace[0]["file"];
		$line=$trace[0]["line"];
		$function=$trace[0]["function"];
		$message.="Erreur dans $function(...): ".$this->getMessage()."\n";
		$message.="thrown in $file on line $line\n";
		return $message;
	}
}







