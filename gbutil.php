<?php
/**
 *
 */

/**
 * class GbUtil
 *
 * @todo timeout session (est-ce ici ??)
 * @author Gilles Bouthenot
 * @version 1.01
 *	function str_readfile($filename) : à remplacer par file_get_contents($filename)
 *
 */
Class GbUtil
{
	const GbUtilVERSION="2alpha";

	// pour log
	const LOG_NONE=9;
	const LOG_EMERG=8;
	const LOG_ALERT=7;
	const LOG_CRIT=6;
	const LOG_ERROR=5;
	const LOG_WARNING=4;
	const LOG_NOTICE=3;
	const LOG_INFO=2;
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


	private static $footer="";							// Footer

	private static $starttime=0;

	private static $sqlTime=0;
	private static $sqlNbExec=0;
	private static $sqlNbFetch=0;

	// les variables suivantes sont publiques, parce que gbUtilDb hérite déjà de Zend_Db
	public static $gbdb_instance_total=0;					// Nombre de classes gbdb ouvertes au total
	public static $gbdb_instance_max=0;						// maximum ouvertes simultanément

	protected static $GbTimer_instance_max=0;

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
				$sqltime=self::$sqlTime;
				$sqlpercent=$sqltime*100/$totaltime;
				$sqlExec=self::$sqlNbExec;
				$sqlFetch=self::$sqlNbFetch;
				self::$footer=htmlspecialchars(self::$footer, ENT_QUOTES);
				self::$footer.=sprintf("Total time: %s s (%.2f%% sql) sqlExec:%d sqlFetch:%d ", GbUtil::roundCeil($totaltime), $sqlpercent, $sqlExec, $sqlFetch);
				if (self::$GbTimer_instance_max)
					self::$footer.=sprintf("GbTimer_instances: %s ", self::$GbTimer_instance_max);
				if (self::$gbdb_instance_total)
					self::$footer.=sprintf("gbdb_total:%s gbdb_max:%s ", self::$gbdb_instance_total, self::$gbdb_instance_max);
				self::$footer.="<br />\n";

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






require_once("Zend/Db.php");

/**
 * Class GbUtilDb
 *
 * @author Gilles Bouthenot
 * @version 1.01
 *
 * @todo retrieve_one
 */
Class GbUtilDb extends Zend_Db
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $conn;

	/**
	 * Renvoie une nouvelle connexion
	 *
	 * type est le driver à utiliser (MYSQL, OCI8)
	 *
	 * @param array("type"=>"MYSQL/OCI8", "host"=>"", "user"=>"", "pass"=>"", "name"=>"") $aIn
	 * @return GbUtilDb
	 */
	function __construct(array $aIn)
	{
		$user=$pass=$name="";
		$driver=$aIn["type"];
		$host=$aIn["host"];
		if (isset($aIn["user"]))						$user=$aIn["user"];
		if (isset($aIn["pass"]))						$pass=$aIn["pass"];
		if (isset($aIn["name"]))						$name=$aIn["name"];
		if     (strtoupper($driver)=="MYSQL")				$driver="Pdo_Mysql";
		elseif (strtoupper($driver)=="OCI8")				$driver="Pdo_Oci";
		elseif (strtoupper($driver)=="OCI")					$driver="Pdo_Oci";
		elseif (strtoupper($driver)=="PDO_OCI")			$driver="Pdo_Oci";
		elseif (strtoupper($driver)=="PDO_MYSQL")		$driver="Pdo_Mysql";
		elseif (strtoupper($driver)=="MYSQLI")			$driver="Pdo_Mysql";
		elseif (strtoupper($driver)=="ORACLE")			$driver="Pdo_Oci";

		try
		{
			$this->conn=Zend_Db::factory($driver, array("host"=>$host, "username"=>$user, "password"=>$pass, "dbname"=>$name));
			$conn=$this->conn->getConnection();
			if ($driver=="Pdo_Oci")
			{
				$conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
			}
		} catch (Exception $e)
		{
			throw new GbUtilException($e->getMessage());
		}

		GbUtil::$gbdb_instance_total++;
		GbUtil::$gbdb_instance_max++;
	}

	function __destruct()
	{
		GbUtil::$gbdb_instance_max--;
	}

	function fetchAll($a, $b)
	{
		return $this->conn->fetchAll($a, $b);
	}

	function fetchAssoc($a, $b)
	{
		return $this->conn->fetchAssoc($a, $b);
	}

	function query($sql, $bindarguments=array())
	{
		return $this->conn->query($sql, $bindargument);
	}


	/**
	 * Execute du sql (PDO seulement)
	 */
	function exec($sql)
	{
		return $this->conn->getConnection()->exec($sql);
	}

	/**
	 * Renvoie toutes les lignes d'un select
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array[optional] $bindargurment exemple array("PE2")
	 * @param string[optional] $index Si spécifié, utilise la colonne comme clé
	 * @param string[optional] $col Si spécifié, ne renvoie que cette colonne
	 *
	 * @return array|string
	 * @throws GbUtilException
	 *
	 *  exemple:
	 *
	 * $dbGE=new GbDb(array("type"=>"mysql", "host"=>"127.0.0.1", "user"=>"gestion_e", "pass"=>"***REMOVED***" ,"base"=>"gestion_e", "notPersistent"=>false));
	 * $a=$dbGE->retrieve_one("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login");
	 *  renvoie "1erlogin"
	 *
	 * $a=$dbGE->retrieve_one("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "");
	 *  renvoie array("usa_login=>"1er login", "usa_idprothee"=>123)
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "", "");
	 *  renvoie array[0]=("usa_login=>"1er login", "usa_idprothee"=>123)
	 *  renvoie array[n]=("usa_login=>"2nd login", "usa_idprothee"=>456)
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login", "");
	 *  renvoie array["1erlogin"]=("usa_idprothee"=>123)
	 *  renvoie array["2ndlogin"]=("usa_idprothee"=>456)
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login", "usa_idprothee");
	 *  renvoie array["1erlogin"]=123
	 *  renvoie array["2ndlogin"]=456
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "", "usa_idprothee");
	 *  renvoie array[0]=123
	 *  renvoie array[1]=456
	 */
	public function retrieve_all($sql, $bindargurment=array(), $index="", $col="")
	{
		if ($bindargurment===False)
			$bindargurment=array();

			try
		{
			/**
			 * @var Zend_Db_Statement
			 */
			$stmt=$this->conn->query($sql, $bindargurment);

			$fCol=(strlen($col)>0);						// True si on veut juste une valeur
			$fIdx=(strlen($index)>0);					// True si on veut indexer

			$ret=array();
			if ($fCol && !$fIdx)
			{	// on veut array[numero]=valeur de la colonne
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
					$ret[]=$res[$col];
			}
			elseif ($fCol && $fIdx)
			{	// on veut array[index]=valeur de la colonne
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
				{
					$key=$res[$index];
					unset($res[$index]);
					$ret[$key]=$res[$col];
				}
			}
			elseif (!$fCol && !$fIdx)
			{	//on veut juste un array[numero]=array(colonnes=>valeur)
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
					$ret[]=$res;
			}
			elseif (!$fCol && $fIdx)
			{	//on veut un array[index]=array(colonnes=>valeur)
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
				{
					$key=$res[$index];
					unset($res[$index]);
					$ret[$key]=$res;
				}
			}

			return $ret;

		} catch (Exception $e){
			throw new GbUtilException($e);
		}

	}

	/**
	 * Renvoie la première ligne d'un select
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array[optional] $bindargurment exemple array("PE2")
	 * @param string[optional] $col Si spécifié, renvoie directement la valeur
	 *
	 * @return array|string|false
	 * @throws GbUtilException
	 */
	public function retrieve_one($sql, $bindargurment=array(), $col="")
	{
		if ($bindargurment===False)
			$bindargurment=array();
		try
		{
			$stmt=$this->conn->query($sql, $bindargurment);

			$fCol=(strlen($col)>0);						// True si on veut juste une valeur

			$ret=array();
			if ($fCol)
			{	// on veut juste la valeur
				$res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
				if ($res===false)
					return false;
				$ret=$res[$col];
			}
			else
			{	//on veut un array
				$res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
				if ($res===false)
					return false;
				$ret=$res;
			}

			return $ret;
		} catch (Exception $e){
			throw new GbUtilException($e);
		}
	}

	public function beginTransaction()
	{
		return $this->conn->beginTransaction();
	}

	public function rollBack()
	{
		return $this->conn->rollBack();
	}

	public function commit()
	{
		return $this->conn->commit();
	}

	/**
	 * SQL update
	 *
	 * @param string $table
	 * @param array $data array("col"=>"val"...)
	 * @param array[optional] $where array("col='val'", ...)
	 * @return int nombre de lignes modifiées
	 */
	public function update($table, array $data, array $where=array())
	{
		return $this->conn->update($table, $data, $where);
	}

	/**
	 * SQL delete
	 *
	 * @param string $table
	 * @param array[optional] $where array("col='val'", ...)
	 * @return int nombre de lignes modifiées
	 */
	public function delete($table, array $where)
	{
		return $this->conn->delete($table, $where);
	}

	/**
	 * SQL insert
	 *
	 * @param string $table
	 * @param array $data array("col"=>"val"...)
	 * @return int nombre de lignes modifiées
	 */
	public function insert($table, array $data)
	{
		return $this->conn->insert($table, $data);
	}

	/**
	 * Regarde Combien de lignes correspondant à $where existe dans la table $data
	 * 0?: insére nouvelle ligne. 1?: met à jour la ligne. 2+: Throws exception
	 *
	 *
	 * @param string $table Table à mettre à jour
	 * @param array $data Données à modifier
	 * @param array[optional] $where array("col='val'", ...)
	 * @throws GbUtilException
	 */
	public function replace($table, array $data, array $where)
	{
		try {
			// compte le nombre de lignes correspondantes
			$select=$this->conn->select();
			$select->from($table, array("A"=>"COUNT(*)"));
			foreach ($where as $w) {
				$select->where($w);
			}
			$stmt=$this->conn->query($select);
			$nb=$stmt->fetchAll();
			$nb=$nb[0]["A"];
			if ($nb==0) {
				// Insertion nouvelle ligne: ajoute le where array("col=val"...)
				foreach ($where as $w) {
					$pos=strpos($w, '=');
					if ($pos===false)	throw new GbUtilException("= introuvable dans clause where !");
					$col=substr($w, 0, $pos);
					$val=substr($w, $pos+1);
					//enlève les quote autour de $val
					if     (substr($val,0,1)=="'" && substr($val,-1)=="'")	$val=substr($val, 1, -1);
					elseif (substr($val,0,1)=='"' && substr($val,-1)=='"')	$val=substr($val, 1, -1);
					else throw new GbUtilException("Pas de guillements trouvés dans la clause where !");
					$data[$col]=$val;
				}
				return $this->conn->insert($table, $data);
			}
			elseif ($nb==1) {
				return $this->conn->update($table, $data, $where);
			}
			else {
				throw new GbUtilException("replace impossible: plus d'une ligne correspond !");
			}
		} catch (Exception $e)
		{
			throw new GbUtilException($e);
		}
	}

	/**
	 * Quote une chaîne
	 *
	 * @param string $var Chaine à quoter
	 * @return string Chaine quotée
	 */
	public function quote($var)
	{
		return $this->conn->quote($var);
	}

	/**
	 * Quote une valeur dans une chaine
	 *
	 * @param string $text ex SELECT * WHERE uid=?
	 * @param  string/array $value ex login (pas de quote !)
	 * @return string chaine quotée
	 */
	public function quoteInto($text, $value)
	{	if (is_array($value))
		{
			foreach($value as $val)
			{
				$pos = strpos($text, "?");
				if ($pos=== false)
					break;
				else
					$text=substr($text, 0, $pos).$val.substr($text, $pos+1);
			}
		}
		else
			return $this->conn->quoteInto($text, $value);
	}
}




Class GbForm
{

	protected $formElements=array();
	protected $where;
	protected $tableName;
	protected static $fPostIndicator=false;

  protected $_commonRegex = array(
		'HexColor'        => '/^#?([\dA-F]{3}){1,2}$/i',
		'UsTelephone'     => '/^\(?([2-9]\d{2})\)?[\.\s-]?([2-4|6-9]\d\d|5([0-4|6-9]\d|\d[0-4|6-9]))[\.\s-]?(\d{4})$/',
		'Email'           => '/(^[\w\.!#$%"*+\/=?`{}|~^-]+)@(([-\w]+\.)+[A-Za-z]{2,})$/',
		'Url'             => '/^(https?|ftp):\/\/([-\w]+\.)+[A-Za-z]{2,}(:\d+)?([\\\\\/]\S+)*?[\\\\\/]?(\?\S*)?$/i',
		'PositiveInteger' => '/^\d+$/',
		'RelativeInteger' => '/^-?\d+$/',
		'DecimalNumber'   => '/^-?(\d*\.)?\d+$/',
		'AlphaNumeric'    => '/^[\w\s]+$/i',
		'DateFr'          => '/^$/',
		'PostalCodeFr'    => '/^[0-9]{5}$/',
		'Year'            => '/^(((19)|(20))[0-9]{2})$/', // aaaa 1900<=aaaa<=2099
		'Year20xx'        => '/^(20[0-9]{2})$/',          // aaaa 2000<=aaaa<=2099
		'DateFr'          => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/(((19)|(20))[0-9]{2})$/',	// jj/mm/aaaa   \1:jj \2:mm \3:aaaa   1900<=aaaa<=2099
		'DateFr20xx'      => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/($20[0-9]{2})/',          // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   2000<=aaaa<=2099
	);


	public function __construct($tableName="", array $where=array())
	{
		$this->tableName=$tableName;
		$this->where=$where;
	}

	/**
	 * Ajoute un élément
	 *
	 * @param string $nom Nom unique, défini le NAME de l'élément doit commencer par une lettre et ne comporter que des caractères alphanumériques
	 * @param array $aParams
	 * @throws GbUtilException
	 */
	public function addElement($nom, array $aParams)
	{
		// $aParams["type"]="SELECT":
		// $aParams["args"]        : array["default"]=array(value=>libelle) (value est recodé dans le html mais renvoie la bonne valeur)
		// $aParams["dbCol"]       : optionnel: nom de la colonne
		// $aParams["fMandatory"]  : doit être rempli ? défaut: false
		// $aParams["classOK"]     : nom de la classe pour élément valide défaut: GBFORM_OK
		// $aParams["classNOK"]    : nom de la classe pour élément non valide défaut: GBFORM_NOK
		// $aParams["classERROR"]  : nom de la classe pour erreur: GBFORM_ERROR
		// $aParams["preInput"]    :
		// $aParams["inInput"]     :
		// $aParams["postInput"]   :
		// renseignés automatiquement:
		// $aParams["class"]       : nom de la classe en cours
		// $aParams["errorMsg"]    : message d'erreur

		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9]*/", $nom))
			throw new GbUtilException("Nom de variable de formulaire invalide");

		if (isset($this->formElement[$nom]))
			throw new GbUtilException("Nom de variable de formulaire déjà défini");

		if (!isset($aParams["type"]))
			throw new GbUtilException("Type de variable de formulaire non précisé");

		if (!isset($aParams["fMandatory"]))
			$aParams["fMandatory"]=false;
		if (!isset($aParams["preInput"]))
			$aParams["preInput"]="";
		if (!isset($aParams["inInput"]))
			$aParams["inInput"]="";
		if (!isset($aParams["postInput"]))
			$aParams["postInput"]="";
		if (!isset($aParams["classOK"]))
			$aParams["classOK"]="GBFORM_OK";
		if (!isset($aParams["classNOK"]))
			$aParams["classNOK"]="GBFORM_NOK";
		if (!isset($aParams["classERROR"]))
			$aParams["classERROR"]="GBFORM_ERROR";
		if (!isset($aParams["class"]))
			$aParams["class"]=$aParams["classOK"];
		$aParams["errorMsg"]="";

		$type=$aParams["type"];
		switch($type)
		{
			case "SELECT":
				if (!isset($aParams["args"]) || !is_array($aParams["args"]))
					throw new gbUtilException("Paramètres de $nom incorrects");
				//remplit value avec le numéro sélectionné.
				$num=0;
				foreach($aParams["args"] as $ordre=>$val) {
					if ($ordre==="default") {
						unset($aParams["args"]["default"]);
						$aParams["args"][$num]=$val;
						$aParams["value"]=$num;
					}
					$num++;
				}
				if (!isset($aParams["value"]))
					$aParams["value"]="0";		// par défaut, 1er élément de la liste
				$this->formElements[$nom]=$aParams;
				break;

			case "TEXT":
				if (isset($aParams["args"]["regexp"])){
					$regexp=&$aParams["args"]["regexp"];
					if (isset($this->_commonRegex[$regexp])) {
						//regexp connu: remplace par le contenu
						$regexp=$this->_commonRegex[$regexp];
					}
				}
				if (!isset($aParams["value"]))
					$aParams["value"]="";		// par défaut, chaine vide
				$this->formElements[$nom]=$aParams;
				break;

			case "CHECKBOX":
				if (isset($aParams["value"]) && $aParams["value"]==true)
					$aParams["value"]=true;
				else
					$aParams["value"]=false;
				$this->formElements[$nom]=$aParams;
				break;

			case "RADIO":
				if (!isset($aParams["value"]))
					$aParams["value"]="";		// par défaut, chaine vide
				$this->formElements[$nom]=$aParams;
				break;

				default:
				throw new GbUtilException("Type de variable de formulaire inconnu pour $nom");
		}

	}



	public function set($nom, $value)
	{
		if (!isset($this->formElements[$nom]))
			throw new gbUtilException("Set impossible: nom=$nom non défini");

		$type=$this->formElements[$nom]["type"];
		if ($type=="SELECT") {
			foreach ($this->formElements[$nom]["args"] as $ordre=>$val) {
				if ($val[0]==$value) {
					$value=$ordre;
					break;
				}
			}
		}
		$this->formElements[$nom]["value"]=$value;
	}


	public function get($nom)
	{
		if (!isset($this->formElements[$nom]))
			throw new GbUtilException("Set impossible: nom=$nom non défini");

		$value=$this->formElements[$nom]["value"];

		if ($this->formElements[$nom]["type"]=="SELECT" && isset($this->formElements[$nom]["args"][$value])) {
			$value=$this->formElements[$nom]["args"][$value][0];
		}
		return $value;
	}

	/**
	 * Renvoit le code HTML approprié (valeur par défaut, préselectionné, etc)
	 *
	 * @param string $nom
	 * @param string[optional] $radioValue
	 * @throws GbUtilException
	 */
	public function getHtml($nom, $radioValue="")
	{
		if (!isset($this->formElements[$nom])) {
			throw new GbUtilException("Variable de formulaire inexistante");
		}

		$ret="";

		if (self::$fPostIndicator==false) {
			// positionnement de la variable statique indiquand que l'indicateur a été mis.
			$ret.="<input type='hidden' name='GBFORMPOST' value='true' />\n";
			self::$fPostIndicator=true;
		}

		$aElement=$this->formElements[$nom];
		$class=$aElement["class"];
		$ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
		$ret.=$aElement["preInput"];

		$type=$aElement["type"];
		$value=$aElement["value"];
		switch ($type) {
			case "SELECT":
				$aValues=$aElement["args"];
				$html=$aElement["inInput"];
				$ret.="<select id='GBFORM_$nom' name='GBFORM_$nom' $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();'>\n";
				$num=0;
				foreach ($aValues as $ordre=>$aOption){
					$sVal=htmlspecialchars($aOption[0], ENT_QUOTES);
					$sLib=htmlspecialchars($aOption[1], ENT_QUOTES);
					$sSelected="";
					if ($ordre==$value)
						$sSelected="selected='selected'";
					$ret.="<option value='$num' $sSelected>$sLib</option>\n";
					$num++;
				}
				$ret.="</select>\n";
				break;

			case "TEXT":
				$sValue=htmlspecialchars($value, ENT_QUOTES);
				$ret.="<input type='text' id='GBFORM_$nom' name='GBFORM_$nom' value='$sValue' onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
				break;

			case "CHECKBOX":
				$sValue="";
				if ($value==true)
					$sValue=" checked='checked'";
				$ret.="<input type='checkbox' id='GBFORM_$nom' name='GBFORM_$nom' value='true' $sValue onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
				break;

			case "RADIO":
				$sValue="";
				if ($value==$radioValue)
					$sValue=" checked='checked'";
				$ret.="<input type='radio' id='GBFORM_$nom' name='GBFORM_$nom' value='$radioValue' $sValue onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
				break;

			default:
				throw new GbUtilException("Type inconnu");
		}
		$errorMsg=$aElement["errorMsg"];
		if (strlen($errorMsg)) {
			$class=$aElement["classERROR"];
			$ret.="<div class='$class'>$errorMsg</div>";
		}
		$ret.=$aElement["postInput"];
		$ret.="</div>\n";

		$js=$this->getJavascript($nom);
		if (strlen($js))
		{
			$ret.="<script type='text/javascript'>\n";
			$ret.="function validate_GBFORM_$nom()\n";
			$ret.="{\n";
			$ret.=$js;
			$ret.="}\n";
			$ret.="</script>\n";
		}
		return $ret;
	}


	/**
	 * Change la classe d'un elément
	 *
	 * @param string $nom
	 * @param boolean|string $class : false/true: met a classNOK/classOK string: met a la classe spécifiée
	 * @throws GbUtilException
	 */
	public function setClass($nom, $class=false)
	{
		if (!isset($this->formElements[$nom]))
			throw new GbUtilException("Element de fomulaire non défini");
		if ($class===false) {
			$class=$this->formElements[$nom]["classNOK"];
		} elseif ($class===true) {
			$class=$this->formElements[$nom]["classOK"];
		}

		$this->formElements[$nom]["class"]=$class;
	}



	/**
	 * Change le message d'erreur d'un elément
	 *
	 * @param string $nom
	 * @param string $errorMsg
	 * @throws GbUtilException
	 */
	public function setErrorMsg($nom, $errorMsg="")
	{
		if (!isset($this->formElements[$nom]))
			throw new GbUtilException("Element de fomulaire non défini");
		$this->formElements[$nom]["errorMsg"]=$errorMsg;
	}


	/**
	 * Renvoie le code javascript pour la validation dynamique
	 *
	 * @param $nom string
	 */
	protected function getJavascript($nom)
	{
		$ret="";
		if (!isset($this->formElements[$nom])) {
			throw new GbUtilException("Variable de formulaire inexistante");
		}
		$aElement=$this->formElements[$nom];

		$type=$aElement["type"];
		switch ($type) {
			case "SELECT":
				$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
				// attention utilise prototype String.strip()
				$ret.="var value=remove_accents(\$F('GBFORM_$nom').strip());\n";
				if ($aElement["fMandatory"]) {
					$aValues="";
					foreach($aElement["args"] as $ordre=>$val) {
						$val=$val[0];
						if ($val===false) $val="false";
						$aValues[]="'$ordre':'$val'";
					}
					$ret.="var GBFORM_{$nom}_values = { ".implode(", ",$aValues)."};\n";
					$ret.="if ((GBFORM_{$nom}_values[value])=='false') {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				break;

			case "TEXT":
				$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
				// attention utilise prototype String.strip()
				$ret.="var value=remove_accents(\$F('GBFORM_$nom').strip());\n";
				if ($aElement["fMandatory"]) {
					$ret.="if (value=='') {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				if (isset($aElement["args"]["regexp"])){
					$regexp=$aElement["args"]["regexp"];
					if (isset($this->_commonRegex[$regexp])) {
						//regexp connu: remplace par le contenu
						$regexp=$this->_commonRegex[$regexp];
					}
					$ret.="var regexp=$regexp\n";
					$ret.="if (!regexp.test(value)) {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				if (isset($aElement["MINVALUE"])){
					$aMinValues=$aElement["MINVALUE"];
					foreach ($aMinValues as $borne) {
						$ret.=" var bornevalue=value;\n";
						if (is_array($borne) && isset($aElement["args"]["regexp"])) {
							// si array, alors extrait la valeur du regexp avant de comparer
							$ret.=" var bornevalue=value.replace({$aElement["args"]["regexp"]}, \"{$borne[0]}\");\n";
							$borne=$borne[1];
						}
						else {
							$ret.=" var bornevalue=value;\n";
						}
						if (strpos($borne, "GBFORM_")===0) {
							// borne commence par GBFORM_
							$borne="\$F('$borne')";
						}
						$ret.=" var borne=eval({$borne});\n";
						$ret.=" if (bornevalue < borne) {";
						$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
						$ret.="}\n";
					}
				}
				if (isset($aElement["MAXVALUE"])){
					$aMaxValues=$aElement["MAXVALUE"];
					foreach ($aMaxValues as $borne) {
						$ret.=" var bornevalue=value;\n";
						if (is_array($borne) && isset($aElement["args"]["regexp"])) {
							// si array, alors extrait la valeur du regexp avant de comparer
							$ret.=" var bornevalue=value.replace({$aElement["args"]["regexp"]}, \"{$borne[0]}\");\n";
							$borne=$borne[1];
						}
						else {
							$ret.=" var bornevalue=value;\n";
						}
						if (strpos($borne, "GBFORM_")===0) {
							// borne commence par GBFORM_
							$borne="\$F('$borne')";
						}
						$ret.=" var borne=eval({$borne});\n";
						$ret.=" if (bornevalue > borne) {";
						$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
						$ret.="}\n";
					}
				}
				break;

			case "CHECKBOX":
				$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
				if ($aElement["fMandatory"]) {
					$ret.="var value=\$F('GBFORM_$nom');\n";
					$ret.="if (value!='true') {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				break;

			case "RADIO":
				break;

			default:
				throw new GbUtilException("Type inconnu");
		}
		return $ret;

	}

	/**
	 * Remplit les valeurs depuis la base de données
	 *
	 * @param GbUtilDb $db
	 * @return boolean true si données trouvées
	 */
	public function getFromDb(GbUtilDb $db)
	{
		//todo: checkbox
		// obient le nom des colonnes
		$aCols=array();
		foreach ($this->formElements as $nom=>$aElement) {
			if (isset($aElement["dbCol"])) {
				$aCols[$nom]=$aElement["dbCol"];
			}
		}

		if (count($aCols)==0) {
			return false;
		}

		$sql="SELECT ".implode(", ", $aCols)." FROM ".$this->tableName;
		if (count($this->where)) {
			$sql.=" WHERE";
			$sWhere="";
			foreach ($this->where as $w)
			{ if (strlen($sWhere)) {
					$sWhere.=" AND";
				}
				$sWhere.=" $w";
			}
			$sql.=$sWhere;
		}

		$aLigne=$db->retrieve_one($sql);
		if ($aLigne===false) {
		// La requête n'a pas renvoyé de ligne
			return false;
		}

		// La requête a renvoyé une ligne
		foreach ($aCols as $nom=>$dbcol) {
			$this->set($nom, $aLigne[$dbcol]);
		}
		return true;
	}


	/**
	 * Insère/update les valeurs dans la bdd
	 *
	 * @param GbUtilDb $db
	 * @param array $moreData
	 * @return boolean true si données ecrites
	 */
	public function putInDb(GbUtilDb $db, array $moreData=array())
	{
		//todo: checkbox
		// obient le nom des colonnes
		$aCols=$moreData;
		foreach ($this->formElements as $nom=>$aElement) {
			if (isset($aElement["dbCol"])) {
				$col=$aElement["dbCol"];
				$val=$this->get($nom);
				$aCols[$col]=$val;
			}
		}

		if (count($aCols)==0) {
			return false;
		}

		$nb=$db->replace($this->tableName, $aCols, $this->where);
		if ($nb)
			return true;
		else
			return false;
	}



	/**
	 * Remplit les valeurs depuis $_POST
	 * @return true si données trouvées
	 */
	public function getFromPost()
	{
		$fPost=false;
		if (isset($_POST["GBFORMPOST"])) {
			// detecte que le formulaire a été soumis. Utile pour les checkbox
			$fPost=true;
		}
		foreach ($this->formElements as $nom=>$aElement) {
			$type=$aElement["type"];
			if ($fPost && $type=="CHECKBOX") {
				// met les checkbox à false
				$this->formElements[$nom]["value"]=false;
			}
			if (isset($_POST["GBFORM_".$nom])) {
					$this->formElements[$nom]["value"]=$_POST["GBFORM_".$nom];
					$fPost=true;
			}
		}
		return $fPost;
	}


	/**
	 * Valide le formulaire
	 * En cas d'erreur, appelle $this->setClass() et $this->setErrorMsg pour chaque $nom incorrect
	 *
	 * @return array("nom" => "erreur") ou true si aucune erreur (attention utiliser ===)
	 */
	public function validate()
	{
		$aErrs=array();
		foreach ($this->formElements as $nom=>$aElement) {
			$type=$aElement["type"];
			$value=strtolower(GbUtil::mystrtoupper(trim($aElement["value"])));

			switch ($type) {
				case "SELECT":
					// Vérifie que la valeur est bien dans la liste et maj $value
					if (isset($aElement["args"][$value])) {
						$value=$aElement["args"][$value][0];
					} else {
						$aErrs[$nom]="Choix invalide";
						continue;
					}

				break;
				case "TEXT":
					if (strlen($value) && isset($aElement["args"]["regexp"])) {
						$regexp=$aElement["args"]["regexp"];
						if (!preg_match($regexp, $value)) {
							$aErrs[$nom]="Valeur incorrecte";
							continue;
						}
					}
					if (strlen($value) && isset($aElement["MINVALUE"])) {
						$aBornes=$aElement["MINVALUE"];
						foreach ($aBornes as $borne) {
							$bornevalue=$value;
							if (is_array($borne) && isset($aElement["args"]["regexp"])) {
								// si array, alors extrait la valeur du regexp avant de comparer
								$bornevalue=preg_replace( $aElement["args"]["regexp"],$borne[0], $value);
								$borne=$borne[1];
							}
							$sBorne=$borne;
							if (strpos($borne, "GBFORM_")===0) {
								// borne commence par GBFORM_
								$sBorne=substr($borne,7);
								$borne=$this->get($sBorne);
								$sBorne.=" ($borne)";
							}
							if ($bornevalue < $borne) {
								$aErrs[$nom]="Doit être supérieur ou égal à $sBorne";
								continue;
							}
						}
					}
					if (strlen($value) && isset($aElement["MAXVALUE"])) {
						$aBornes=$aElement["MAXVALUE"];
						foreach ($aBornes as $borne) {
							$bornevalue=$value;
							if (is_array($borne) && isset($aElement["args"]["regexp"])) {
								// si array, alors extrait la valeur du regexp avant de comparer
								$bornevalue=preg_replace( $aElement["args"]["regexp"],$borne[0], $value);
								$borne=$borne[1];
							}
							$sBorne=$borne;
							if (strpos($borne, "GBFORM_")===0) {
								// borne commence par GBFORM_
								$sBorne=substr($borne,7);
								$borne=$this->get($sBorne);
								$sBorne.=" ($borne)";
							}
							if ($bornevalue > $borne) {
								$aErrs[$nom]="Doit être inférieur ou égal à $sBorne";
								continue;
							}
						}
					}


			}

			if ($aElement["fMandatory"]) {
				// Vérifie que le champ et bien rempli
				if ( ($type=="SELECT" && $value===false) || ($type!="SELECT" && strlen($value)==0) ) {
					if ($type=="SELECT")	$aErrs[$nom]="Aucun choix sélectionné";
					elseif ($type=="TEXT")	$aErrs[$nom]="Valeur non renseignée";
					elseif ($type=="CHECKBOX")	$aErrs[$nom]="Case non cochée";
					elseif ($type=="RADIO")	$aErrs[$nom]="?";
					else	$aErrs[$nom]="Champ non renseigné";
					continue;
				}
			}
		}//foreach

		foreach($aErrs as $nom=>$reason)
		{
			$this->setClass($nom, false);
			$this->setErrorMsg($nom, $reason);
		}

		if (count($aErrs)==0)
			return true;
		else
			return $aErrs;
	}


}


