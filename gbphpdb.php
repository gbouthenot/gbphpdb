<?php
ob_start("ob_gzhandler");
error_reporting(0);
if (isset($_GET) 	&& !isset($HTTP_GET_VARS))		$HTTP_GET_VARS=$_GET;
if (isset($_POST) && !isset($HTTP_POST_VARS))		$HTTP_GET_VARS=$_POST;

if (	(isset($globals["debug"]) && $globals["debug"]==1)
	 || (			(isset($_GET["debug"]) && $_GET["debug"]==1)
	 			&&	(		(isset($globals["allow_debug"]) && $globals["allow_debug"])
	 						|| empty($globals["allow_debug"])
	 )	)			)
{
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
	$globals["debug"]=1;
}

define("GBPHPDBVERSION","1.71");

// Variables globales à l'ensemble du projet

//authentification:
define("AUTH_GRAND_TIMEOUT", 90);	// timeout en minutes
define("AUTH_REL_TIMEOUT", 20);		// timeout en minutes

define("STARTTIME", getmicrotime());

$globals["db_needed"]=0;			// 1 si la bd doit être ouverte
$globals["show_footer"]=0;		// 1 pour afficher le footer

// paramètres de la connection à la base de donnée
$globals["db_open"]=0;						// etat de la base (1: ouverte)
$globals["db_type"]="";						// "MYSQL" "OCI" ou "POSTGRESQL"
$globals["db_host"]="localhost";
$globals["db_port"]="";						//3306 pour my, 5432 pour pg, 1521 pour oracle
$globals["db_dbname"]="";
$globals["db_user"]="postgres";
$globals["db_passwd"]="";
$globals["db_handle"]=0;
$globals["db_resultID"]=0;
$globals["db_affectedRows"]=0;
$globals["db_lastOID"]=0;
$globals["db_nbRows"]=0;
$globals["db_nbCols"]=0;
$globals["db_curRow"]=-1;
$globals["nologo"]=0;

$globals["gbdb_instance_total"]=0;					// Nombre de classes gbdb ouvertes au total
$globals["gbdb_instance_current"]=0;				// en ce moment
$globals["gbdb_instance_max"]=0;						// maximum ouvertes simultanément

if (empty($globals["debug"]))
	$globals["debug"]=0;					// 1 si mode debug par défaut
if (empty($globals["allow_debug"]))
	$globals["allow_debug"]=1;			// 1 si on autorise $PHP_GET_VARS["debug"]==1?
$globals["footer"]="";				// le footer

// pour send_headers()
define("P_HTTP"		,0);			// headers HTTP
define("P_CUSTOM"	,1);			// autre (pour gestion complete)
define("P_HTML"		,2);			// dans la balise <HTML>
define("P_HEAD"		,3);			// dans la balise <HEAD>
define("P_XHEAD"	,4);			// après la balise </HEAD>
define("P_BODY"		,5);			// dans la balise <BODY>
define("P_XBODY"	,6);			// après la balise </BODY>
define("P_XHTML"	,7);			// après la balise </HTML>
$globals["html_parse"]=P_HTTP;

$globals["html_title"]="GBPHPDB interface v".GBPHPDBVERSION;
$globals["html_keywords"]="";
$globals["html_description"]="";
$globals["html_author"]="";
$globals["html_copyright"]="";
$globals["html_xurl"]="";
$globals["html_nocache"]="1";
$globals["html_headfile"]="";
$globals["html_headarea"]="";
$globals["html_bodytag"]="";
$globals["html_bodyfile"]="";
$globals["html_bodyarea"]="";
$globals["html_onload"]="";
$globals["footerescape"]=1;

function startup()
{
	global $globals;

	$globals["prevtime"]=STARTTIME;
	$globals["sqlTime"]=0;
	$globals["sqlNbExec"]=0;
	$globals["sqlNbFetch"]=0;
  mt_srand((double)microtime()*1000000);
	srand((double) microtime() * 1000000);

	$globals["str_src"]  =" !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~€?‚ƒ„…†‡ˆ‰Š‹Œ?Ž??‘’“”•–—˜™š›œ?žŸ ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ";
	$globals["str_upper"]=" !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`ABCDEFGHIJKLMNOPQRSTUVWXYZ{|}~€?,F,_†‡ˆ%S‹O?Z??''“”.--˜TS›O?ZY IC£¤¥|§¨C2<¬­R¯°±23'UQ.¸10>¼½¾?AAAAAAACEEEEIIIIDNOOOOOXOUUUUYþBAAAAAAACEEEEIIIIONOOOOO/OUUUUYþ";

	if ($globals["db_needed"])
	{
/*
		if (!function_exists('base_open'))
		{		if     (($fname="gbphpdb/db.p")       && file_exists($fname)) {require_once($fname);}
				elseif (($fname="../gbphpdb/db.p")    && file_exists($fname)) {require_once($fname);}
				elseif (($fname="../../gbphpdb/db.p") && file_exists($fname)) {require_once($fname);}
		}
*/
		if ($globals["db_dbname"])
		{
			$aIn=array();
			$aIn["db_type"]=$globals["db_type"];
			$aIn["db_host"]=$globals["db_host"];
			$aIn["db_port"]=$globals["db_port"];
			$aIn["db_dbname"]=$globals["db_dbname"];
			$aIn["db_user"]=$globals["db_user"];
			$aIn["db_passwd"]=$globals["db_passwd"];
			$aIn["db_open"]=$globals["db_open"];

			$aOut=array();
			$aOut["db_handle"]=0;;
			$aOut["db_open"]=0;;
			$aOut["nbError"]=0;;

			$sResult=base_open($aIn, &$aOut);
			if (check_result($sResult,"base_open", 0)==3)
			{
				$ret="ERROR: impossible d'initialiser la base de donnée. Erreur: ".$sResult;
				echo $ret;
				return $ret;
			}

			$globals["db_handle"]=$aOut["db_handle"];
			$globals["db_open"]=$aOut["db_open"];

			if ($globals["db_type"]=="POSTGRESQL")
			{
				// passe le format de date en JJ/MM/AAAA
				$sql =" SET DATESTYLE TO 'SQL,EUROPEAN';";
				$sResult=sql_exec($sql);
				if (check_result($sResult, "sql_exec".$sql, 0)==3)	return;
			}
		}
	}

	check_result(main(), "main", 0);

	if ($globals["db_open"])
	{ // ferme la base si nécessaire
		sql_free();

		$aIn=array();
		$aIn["db_type"]=$globals["db_type"];
		$aIn["db_handle"]=$globals["db_handle"];
		$aIn["db_open"]=$globals["db_open"];

		$aOut=array();
		$aOut["db_close"]=0;;

		base_close($aIn, &$aOut);

		$globals["db_open"]=!$aOut["db_close"];
	}

	// Affichage du footer
	if ($globals["debug"] || $globals["show_footer"])
	{		$totaltime=getmicrotime()-STARTTIME;
			$sqltime=$globals["sqlTime"];
			$sqlpercent=$sqltime*100/$totaltime;
			$sqlExec=$globals["sqlNbExec"];
			$sqlFetch=$globals["sqlNbFetch"];
			$globals["footer"].=sprintf("Total time: %.1fs (%d%% sql) sqlExec:%d sqlFetch:%d ", $totaltime, $sqlpercent, $sqlExec, $sqlFetch);
			if ($globals["gbdb_instance_total"])
				$globals["footer"].=sprintf("gbdb total:%s max:%s ", $globals["gbdb_instance_total"], $globals["gbdb_instance_max"]);
			$globals["footer"].="<br />\n";

			if ($globals["debug"])
			{
$globals["footer"].="
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

			if ($globals["footerescape"])
				echo "</span></span></span></div></div></div></div></div></p>";
			printf("<div class='gbphpdb_footer'>%s</div>\n", $globals["footer"]);
	}	// Affichage du footer

	$hp=$globals["html_parse"];
	if ($hp>=P_HTML && !$globals["nologo"])
		printf("<!-- built with gbphpdb v%s -->\n", GBPHPDBVERSION);
	elseif (!$globals["nologo"])
		printf("built with gbphpdb v%s\n", GBPHPDBVERSION);

	if ($hp>=P_BODY && $hp<P_XBODY)
		print "</body>\n";
	if ($hp>=P_HTML && $hp<P_XHTML)
		print "</html>\n";
}


function check_result($sResult, $sFunction="", $fPrint=1)
{
	global $globals;

	$res=strtoupper($sResult);
	$result=0;

	if     (!(strpos($res, "ERROR"  ) === false))			$result=3;
	elseif (!(strpos($res, "ERREUR" ) === false))			$result=3;
	elseif (!(strpos($res, "WARNING") === false))			$result=2;
	elseif (strlen($res)>0 && $res!="OK")	  					$result=1;

	if     ($result==3)
		{if ($fPrint) printf("%s()=%s.<BR>\n",$sFunction, $sResult);}
	elseif ($result==2)
		{if ($fPrint) printf("%s()=%s.<BR>\n",$sFunction, $sResult);
									$globals["footer"].=sprintf("%s()=%s<BR>\n",$sFunction, $sResult);}
	elseif ($result==1 && $globals["debug"])
		{if ($fPrint) $globals["footer"].=sprintf("%s()=%s<BR>\n",$sFunction, $sResult);}

	if ($globals["debug"] && $sFunction && $fPrint)
	{
		$time=getmicrotime()-$globals["prevtime"];
		$globals["footer"].=sprintf("%s()=%s (%.2fs)<BR>", $sFunction, $sResult, $time);
		$globals["prevtime"]=getmicrotime();
	}
	return $result;
}


function log_file($sText, $sFName="")
{
	$REMOTE_USER="";          if (isset($_SERVER["REMOTE_USER"]))		       $REMOTE_USER=         $_SERVER["REMOTE_USER"];
	$REMOTE_ADDR="";          if (isset($_SERVER["REMOTE_ADDR"]))		       $REMOTE_ADDR=         $_SERVER["REMOTE_ADDR"];
	$HTTP_X_FORWARDED_FOR=""; if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];

	$sFName2="";

	if ($sFName)
		$sFName2=$sFName;
	elseif (defined("LOGFILE"))
		$sFName2=LOGFILE;

	if ($sFName2)
	{
		$fd=fopen($sFName2, "a");
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

		if (strlen(session_id()))
			$sLog.="sid=".substr(session_id(),0,12)." ";

		if (strlen($REMOTE_USER))
			$sLog.="user=".$REMOTE_USER." ";

		$sLog.=$sText." ";

		$vd=debug_backtrace();
		$vd=$vd[1];

		$sLog2="";
		foreach ($vd["args"] as $num=>$arg)
		{	if ($num)			$sLog2.=", ";

		    $pr=var_export($arg, true);
		    $pr=preg_replace("/^ +/m", "", $pr);                // enlève les espaces en début de ligne
		    $pr=preg_replace("/,\n\)/m", ")", $pr);             // remplace les ,) par )
		    $pr=preg_replace("/,$/m", ", ", $pr);               // remplace "," par ", " en fin de ligne
		    $pr=str_replace("\n", "", $pr);                     // met tout sur une ligne
		    $pr=str_replace(" => ", "=>", $pr);                 // enlève les espaces avant et après "=>"
		    $pr=str_replace("array (", "array( ", $pr);         // formate array (
				$sLog2.=$pr;
		}

		if (strlen($sLog2)>100)
			$sLog2=substr($sLog2, 0, 100)."...";

		$sLog.="file:".substr($vd["file"],-30)." line:".$vd["line"]." in ".$vd["function"]."($sLog2)";

		$sLog.="\n";
		fwrite($fd, $sLog);
		fclose ($fd);
	}
}


// renvoie dans outTime l'heure qui correspond à la chaine $sTime.
// (nombre de secondes depuis 01/01/1970)
// $sTime doit être formaté en "jj/mm/aaaa hh:mm:ss" ou "jj/mm/aaaa hh:mm:ss.xxxxxx"
// ou "aaaa-mm-jj hh:mm:ss" ou "aaaa-mm-jj hh:mm:ss.xxxxxx"

function str_to_time($sTime, $outTime)
{
	$sTime=gb_date_fr($sTime);
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

// renvoit une chaine composée des premiers mots seulement de la chaine donnée
// on donne une longueur minimal a la chaine avant laquelle on ne coupe pas.
function create_nom($prenoms, $lmin=4)
{	trim($prenoms);
	$out="";
	for ($i=0; $i<strlen($prenoms); $i++)
	{	$c=substr($prenoms,$i,1);
		if ($c==" " && $i>=$lmin)			break;
		$out.=$c;
	}
	return $out;
}



// conversion en majuscule, enlève les accents
function mystrtoupper($s)
{
	global $globals;
	return strtr($s, $globals["str_src"], $globals["str_upper"]);
}



// si debug est activé, renvoie ($c)debug=1
// $c pouvant être '?' ou '&'
function url_debug($c)
{
	global $globals;
	if ($globals["debug"])
		return $c."debug=".$globals["debug"];
	return "";
}


function getmicrotime()
{
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}


function str_readfile($filename)
{		$ret="";
		$fd=fopen($filename, "r");
		$ret=fread($fd, filesize ($filename));
		fclose ($fd);
		return $ret;
}


function include_file($file)
{	// fait include d'un fichier, mais retourne le résultat dans une chaine
	// cela permet d'inclure un fichier et de l'éxecuter en même temps
	ob_start();
	if (file_exists($file))
		include($file);
	return	ob_get_clean();
}



function send_headers($fPrint=1)
{
	global $globals;

	$ret="";
	$glo_parse=$globals["html_parse"];

	if ($glo_parse!=P_CUSTOM)
	{
		if ($glo_parse<P_HTML)
		{
/*
//			$ret.="<?xml version='1.0'  encoding='ISO-8859-1'?>\n";
//			$ret.="<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
//			$ret.="<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='fr' lang='fr'>\n";
*/

  		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passé
  		header("Content-Type: text/html; charset=iso-8859-15");

			$ret.="<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>";
			$ret.="<html lang='fr'>\n";
		}

		if ($glo_parse<P_HEAD)
			$ret.="<head>\n";

		if ($glo_parse<P_XHEAD)
		{
			if ($globals["html_title"])					$ret.=sprintf("<title>%s</title>\n", $globals["html_title"]);
			if ($globals["html_description"])		$ret.=sprintf("<meta name='description' content=\"%s\" >\n", $globals["html_description"]);
			if ($globals["html_keywords"])			$ret.=sprintf("<meta name='keywords'    content=\"%s\" >\n", $globals["html_keywords"]);
			if ($globals["html_author"])				$ret.=sprintf("<meta name='author'      content=\"%s\" >\n", $globals["html_author"]);
			if ($globals["html_copyright"])			$ret.=sprintf("<meta name='copyright'   content=\"%s\" >\n", $globals["html_copyright"]);
			if ($globals["html_xurl"])					$ret.=sprintf("<meta name='x-URL'       content=\"%s\" >\n", $globals["html_xurl"]);
			if ($globals["html_nocache"])				$ret.=        "<meta http-equiv=\"Expires\" content=\"Tue, 15 Feb 1990 00:00:01 GMT\" >\n";
			$ret.=                                    sprintf("<meta name='x-scriptVersion' content='%s' >\n", GBPHPDBVERSION);
			$ret.="<meta http-equiv='Content-Type'        content='text/html;  charset=ISO-8859-15' >\n";
			$ret.="<meta http-equiv='Content-Script-Type' content='text/javascript' >\n";
			$ret.="<meta http-equiv='Content-Style-Type'  content='text/css' >\n";
			$ret.="<meta http-equiv='Content-Language' 		content='fr' >\n";
			if (strlen($globals["html_headarea"]))			$ret.=$globals["html_headarea"];
			if (strlen($globals["html_headfile"]))			$ret.=include_file($globals["html_headfile"]);
			$ret.="</head>\n";
		}

		if ($glo_parse<P_BODY)
		{
			if (strlen($globals["html_bodytag"]))				$ret.=$globals["html_bodytag"];
			else																$ret.="<body>";
			if (strlen($globals["html_bodyfile"]))			$ret.=include_file($globals["html_bodyfile"]);
			if (strlen($globals["html_bodyarea"]))			$ret.=$globals["html_bodyarea"];
			$ret.="\n";
		}

		$globals["html_parse"]=P_BODY;
	}
	if ($fPrint)
		echo $ret;
	else
		return $ret;
} // function send_headers


// convertit, si nécessaire une date au format YYYY-MM-DD en DD/MM/YYYY
function gb_date_fr($d="")
{
  if ($d=="")
    return date("d/m/Y H:i:s");

	if (substr($d,4,1)=='-')
	{	// date au format YYYY-MM-DD
		list($y,$m,$d)=split("-",$d);
		$d=substr($d,0,2).'/'.$m.'/'.$y.substr($d,2);
	}
	return $d;
}

// convertit, si nécessaire une date au format DD/MM/YYYY en YYYY-MM-DD
function gb_date_mysql($d="")
{
  if ($d=="")
    return date("Y-m-d H:i:s");
	if (substr($d,5,1)=='/')
	{	// date au format DD/MM/YYYY
		list($d,$m,$y)=split('/',$d);
		$d=substr($y,0,4).'-'.$m.'-'.$d.substr($y,4);
	}
	return $d;
}


// enlève les slashes des données GET, POST, COOKIE (gpc), si magic_quotes_gpc est actif
function gpcstripslashes($str)
{
  if (get_magic_quotes_gpc())
    return stripslashes($str);
  return $str;
}



// gpc_strip_all() : enlève les slashes sur toutes les données $_GET $_POST, $_COOKIE si magic_quote_gpc est actif
function gpc_strip_all()
{
  if (!get_magic_quotes_gpc())
    return;

  foreach($_GET as $key=>$value)
    $_GET[$key]=stripslashes($value);

  foreach($_POST as $key=>$value)
    $_POST[$key]=stripslashes($value);

  foreach($_COOKIE as $key=>$value)
    $_COOKIE[$key]=stripslashes($value);
}





// db.p:
function base_open($aDBparams, $aOut)
{	global $globals;
	$prevtime=getmicrotime();
	$host=$aDBparams["db_host"];
	$port=$aDBparams["db_port"];
	$dbname=$aDBparams["db_dbname"];
	$user=$aDBparams["db_user"];
	$passwd=$aDBparams["db_passwd"];
	$db_open=$aDBparams["db_open"];

	$conn_str="";

	$aOut["nbError"]=0;
	$aOut["db_open"]=0;
	$aOut["db_handle"]=0;

	// variables locales:
	$hDbId=0;			// Contient le handle de connexion avec la base

	if ($db_open)
	{	// base déjà ouverte : erreur
		$aOut["nbError"]++;
		return "Error: base deja ouverte";
	}

	switch($aDBparams["db_type"])
	{
		case "POSTGRESQL":
			if (!$port)
				$port=5432;
			$conn_str ="host=".$host;
			$conn_str.=" user=".$user;
			$conn_str.=" password=".$passwd;
			$conn_str.=" port=".$port;
			$conn_str.=" dbname='".$dbname."'";
			$hDbId=pg_connect($conn_str);
			if (!$hDbId)
			{	// erreur
				$aOut["nbError"]++;
				return "Error: impossible de se connecter au serveur ou à la base de données";
			}
			$aOut["db_handle"]=$hDbId;
			$aOut["db_open"]=1;
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			return "";

		case "OCI":
			if (!$port)
				$port=1521;
			$conn_str=sprintf("(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SID=%s)))", $host, $port, $dbname);
			$hDbId=ocilogon($user, $passwd, $conn_str);
			$aOut["db_handle"]=$hDbId;
			$aOut["db_open"]=1;
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			return "";

		case "MYSQL":
			if (!$port)
				$port=3306;
			$hDbId=mysql_connect ($host, $user ,$passwd);
			if (!$hDbId)
			{	// erreur
				$aOut["nbError"]++;
				return "Error: impossible de se connecter au serveur de base de données";
			}
			if (!mysql_select_db ($dbname,$hDbId))
			{	// erreur
				$aOut["nbError"]++;
				mysql_close($hDbId);
				return "Error: impossible de se connecter à cette base";
			}
			$aOut["db_handle"]=$hDbId;
			$aOut["db_open"]=1;
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			return "";

		default:
			$aOut["nbError"]++;
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			return "Error: type de base non géré";
	}
}




function base_close($aDBparams, $aOut)
{	global $globals;
	$prevtime=getmicrotime();
	$handle=$aDBparams["db_handle"];

	$aOut["db_close"]=0;

	switch($aDBparams["db_type"])
	{
		case "POSTGRESQL":
			pg_close($handle);
			$aOut["db_close"]=1;
			$aDBparams["db_open"]=0;
			$aDBparams["db_handle"]="";
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			return "";

		case "OCI":
			ocilogoff($handle);
			$aOut["db_close"]=1;
			$aDBparams["db_open"]=0;
			$aDBparams["db_handle"]="";
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			return "";

		case "MYSQL":
			mysql_close($handle);
			$aOut["db_close"]=1;
			$aDBparams["db_open"]=0;
			$aDBparams["db_handle"]="";
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			return "";

		default:
			$globals["sqlTime"].=getmicrotime()-$prevtime;
			break;
	}
}





function sql_exec($sql, $whichHandle=0, $fClearResult=1)
{
	global $globals;
	$prevtime=getmicrotime();
	$globals["sqlNbExec"]++;

	$handle=0;
	$resultID=0;

	if ($whichHandle)		$handle=$whichHandle;
	else								$handle=$globals["db_handle"];

	if ($globals["db_resultID"] && $fClearResult)
		sql_free();

	switch($globals["db_type"])
	{
		case "POSTGRESQL":
			$resultID=pg_exec($handle, $sql);
			if ($resultID==false)
				return "Error: ".pg_errormessage($handle).'$sql="'.$sql.'"';
			$globals["db_resultID"]=$resultID;

			$ret=pg_cmdtuples($resultID);
			$globals["db_affectedRows"]=$ret;

			$ret=pg_getlastoid($resultID);
			$globals["db_lastOID"]=$ret;

			$ret=pg_numrows($resultID);
			$globals["db_nbRows"]=$ret;

			$ret=pg_numfields($resultID);
			$globals["db_nbCols"]=$ret;
		break;

		case "OCI":
			$resultID=ociparse($handle, $sql);
			if ($resultID==false)
				return "Error: ociparse sql=".$sql;
			$res=ociexecute($resultID, OCI_DEFAULT);
			if ($res==false)
				return "Error: ociexecute sql=".$sql;

			$globals["db_resultID"]=$resultID;

			$ret=OCIRowCount($resultID);
			$globals["db_affectedRows"]=$ret;

			$ret=OCINumCols($resultID);
			$globals["db_nbCols"]=$ret;

//			$globals["db_lastOID"]=$ret;
//			$globals["db_nbRows"]=$ret;
		break;


		case "MYSQL":
			$resultID=mysql_query($sql, $handle);
			if ($resultID==false)				return 'Error: mysql_query('.mysql_errno($handle).','.mysql_error($handle).') $sql="'.$sql.'"';
			$globals["db_affectedRows"]=mysql_affected_rows($handle);
			$globals["db_lastOID"]=     mysql_insert_id    ($handle);
			if(is_resource($resultID))
			{
				$globals["db_resultID"]=    $resultID;
				$globals["db_nbRows"]=      mysql_num_rows     ($resultID);
				$globals["db_nbCols"]=      mysql_num_fields   ($resultID);
			}
		break;
	}

	$globals["db_curRow"]=-1;
	$globals["sqlTime"]+=getmicrotime()-$prevtime;
	return "OK";
}





function sql_free($whichID=0)
{
	$prevtime=getmicrotime();
	global $globals;

	switch($globals["db_type"])
	{
		case "POSTGRESQL":
			if ($whichID)
				pg_freeresult($whichID);
			elseif ($globals["db_resultID"])
			{
				pg_freeresult($globals["db_resultID"]);
				$globals["db_resultID"]=0;
			}
		break;

		case "OCI":
			if ($whichID)
				OCIFreeStatement($whichID);
			elseif ($globals["db_resultID"])
			{
				OCIFreeStatement($globals["db_resultID"]);
				$globals["db_resultID"]=0;
			}
		break;


		case "MYSQL":
			if ($whichID)
				mysql_free_result($whichID);
			elseif ($globals["db_resultID"])
			{
				mysql_free_result($globals["db_resultID"]);
				$globals["db_resultID"]=0;
			}
		break;
	}
	$globals["sqlTime"]+=getmicrotime()-$prevtime;
}


// &$aRow: tableau de destination
//  $whichRow: "next" pour ligne suivante (défaut), "prev" pour ligne précédente sinon le numéro de ligne

function sql_fetch_row($aRow, $whichRow="next", $whichID=0)
{
	global $globals;
	$globals["sqlNbFetch"]++;
	$prevtime=getmicrotime();

	$numRow=0;
	$resultID=0;

	if ($whichID)
		$resultID=$whichID;
	else
		$resultID=$globals["db_resultID"];

	if ($whichRow=="next")
		$numRow=++$globals["db_curRow"];
	elseif ($whichRow=="prev" && $globals["db_curRow"]>1)
		$numRow=--$globals["db_curRow"];
	else
	{	$numRow=$whichRow;
		$globals["db_curRow"]=$numRow;
	}

	switch($globals["db_type"])
	{
		case "POSTGRESQL":
			$aRow=pg_fetch_row($resultID, $numRow);
		break;

		case "OCI":
			if ($whichRow=="next")
				ociFetchInto($resultID, &$aRow, OCI_NUM+OCI_RETURN_NULLS);
			else
				return "error: only next supported with oracle";
		break;

		case "MYSQL":
			if ($whichRow!="next")
				mysql_data_seek($resultID, $numRow);
			$aRow=mysql_fetch_row($resultID);
		break;
	}

	if ($aRow==false)
	{
		$globals["db_curRow"]--;
		return "Warning: sql_fetch_row error";
	}
	$globals["sqlTime"]+=getmicrotime()-$prevtime;
	return "OK";
}




function sql_fetch_array($aRow, $whichRow="next", $whichID=0)
{
	global $globals;
	$prevtime=getmicrotime();
	$globals["sqlNbFetch"]++;

	$numRow=0;
	$resultID=0;

	if ($whichID)
		$resultID=$whichID;
	else
		$resultID=$globals["db_resultID"];

	if ($whichRow=="next")
		$numRow=++$globals["db_curRow"];
	elseif ($whichRow=="prev" && $globals["db_curRow"]>1)
		$numRow=--$globals["db_curRow"];
	else
	{	$numRow=$whichRow;
		$globals["db_curRow"]=$numRow;
	}

	switch($globals["db_type"])
	{
		case "POSTGRESQL":
			$aRow=pg_fetch_array($resultID, $numRow, PGSQL_ASSOC);
		break;

		case "OCI":
			if ($whichRow=="next")
				ociFetchInto($resultID, &$aRow, OCI_ASSOC+OCI_RETURN_NULLS);
			else
				return "error: next not supported with oracle";
		break;

		case "MYSQL":
			if ($whichRow!="next")
				mysql_data_seek($resultID, $numRow);
			$aRow=mysql_fetch_array($resultID, MYSQL_ASSOC);
		break;
	}

	if ($aRow==false)
	{
		$globals["db_curRow"]--;
		return "Warning: sql_fetch_array error";
	}
	$globals["sqlTime"]+=getmicrotime()-$prevtime;
	return "OK";
}



function sql_get_col_names($aRow, $whichID=0)
{
	global $globals;
	$prevtime=getmicrotime();

	$resultID=0;

	if ($whichID)
		$resultID=$whichID;
	else
		$resultID=$globals["db_resultID"];

	switch($globals["db_type"])
	{
		case "POSTGRESQL":
			$nbCol=pg_numfields($resultID);
			if ($nbCol==-1)
				return "Error: sql_get_field_names:pg_numfields";

			$globals["db_nbCols"]=$nbCol;
			for ($runCol=0; $runCol<$nbCol; $runCol++)
				$aRow[$runCol]=pg_fieldname($resultID, $runCol);
		break;

		case "MYSQL":
			$nbCol=mysql_num_fields($resultID);

			$globals["db_nbCols"]=$nbCol;
			for ($runCol=0; $runCol<$nbCol; $runCol++)
				$aRow[$runCol]=mysql_field_name($resultID, $runCol);
		break;
	}

	$globals["sqlTime"]+=getmicrotime()-$prevtime;
	return "OK";
}



/* gbclass.p : */

Class gbdb
{
	var $handle=0;
	var $type=0;
	var	$aResultID=array();
	var $instance_number=0;

	var	$sMsg='';

	function gbdb($aIn)
	{	// constructeur
		global $globals;
		$globals["gbdb_instance_current"]++;
		$globals["gbdb_instance_total"]++;
		if ($globals["gbdb_instance_current"]>$globals["gbdb_instance_max"])	$globals["gbdb_instance_max"]=$globals["gbdb_instance_current"];
		$this->instance_number=$globals["gbdb_instance_total"];
		$prevtime=getmicrotime();
		register_shutdown_function(array(&$this, "_destructor"));

		$port=$user=$passwd="";
		$type=$aIn["type"];
		$host=addslashes($aIn["host"]);
		$dbname=addslashes($aIn["name"]);
		if (isset($aIn["port"]))	$port=addslashes($aIn["port"]);
		if (isset($aIn["user"]))	$user=addslashes($aIn["user"]);
		if (isset($aIn["pass"]))	$passwd=addslashes($aIn["pass"]);
		if     (strtoupper($type)=="PDO_MYSQL")	$type="MYSQL";
		elseif (strtoupper($type)=="PDO_OCI")	$type="OCI";
		elseif (strtoupper($type)=="ORACLE")	$type="OCI";

		switch(strtoupper($aIn["type"]))
		{
			case "POSTGRESQL":
			case "PGSQL":
				if (!$port)				$port=5432;
				$conn_str ="host=".$host;
				$conn_str.=" user=".$user;
				$conn_str.=" password=".$passwd;
				$conn_str.=" port=".$port;
				$conn_str.=" dbname=".$dbname;
				$hDbId=pg_connect($conn_str);
				if (!$hDbId)
				{
					$this->sMsg='Error: impossible de se connecter au serveur ou à la base de données';
					return;
				}
				$this->handle=$hDbId;
				$this->type="PGSQL";
				$globals["sqlTime"]+=getmicrotime()-$prevtime;
				break;

			case "OCI":
				if (!$port)				$port=1521;
				$conn_str=sprintf("(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SID=%s)))", $host, $port, $dbname);
				$hDbId=ocilogon($user, $passwd, $conn_str);
				$this->handle=$hDbId;
				$this->type="OCI";
				$globals["sqlTime"]+=getmicrotime()-$prevtime;
				break;

			case "MYSQL":
				if (!$port)				$port=3306;
//				$hDbId=mysql_connect ($host, $user ,$passwd, true);	// ne pas faire: génére trop de connexion
				$hDbId=mysql_connect ($host, $user ,$passwd, false);
				if (!$hDbId)
				{
					$this->sMsg='Error: impossible de se connecter au serveur ou à la base de données';
					return;
				}

				if (!mysql_select_db ($dbname,$hDbId))
				{	// erreur
					mysql_close($hDbId);
					$this->sMsg='Error: impossible de se connecter à cette base';
					return;
				}
				$this->handle=$hDbId;
				$this->type="MYSQL";
				$globals["sqlTime"]+=getmicrotime()-$prevtime;
				break;

			default:
				$globals["sqlTime"]+=getmicrotime()-$prevtime;
					$this->sMsg='Error: type de base non géré: '.$aIn["type"];
					break;
		}//switch

		if ($globals["debug"])
			$globals["footer"].="gbdb_new ".$this->instance_number." handle: ".$this->handle."<br />\n";

		return;
	}


	function _destructor()
	{
		global $globals;

		if (isset($this->handle) && $this->handle)
		{
			$aLocResultID=$this->aResultID;
			for ($n=0; $n<count($aLocResultID); $n++)
			{
				$to_free["resultID"]=$aLocResultID[$n];
				$this->free($to_free);
			}

			$handle=$this->handle;

			if ($globals["debug"])
				$globals["footer"].="gbdb_delete ".$this->instance_number." handle: ".$handle."<br />\n";

			switch($this->type)
			{
				case "PGSQL":				pg_close($handle);				break;
				case "OCI":					ocilogoff($handle);				break;
				case "MYSQL":				//mysql_close($handle);		Laisser mysql fermer les connexions (réutilisations des ressources)
							break;
			}

			unset($this->handle);
			unset($this->type);

			$globals["gbdb_instance_current"]--;
		}
	}


	function exec($sql, &$aOut)
	{
		global $globals;
		$prevtime=getmicrotime();
		$globals["sqlNbExec"]++;

		$handle=$this->handle;
		if (isset($aOut["resultID"]) && $aOut["resultID"])
		{
			$this->free(&$aOut);
		}

		switch($this->type)
		{
			case "PGSQL":
				$resultID=pg_exec($handle, $sql);
				if ($resultID===false)
					return "Error: ".pg_errormessage($handle).'$sql="'.$sql.'"';

				$aResultID[]=$resultID;							// push the resultID
																						$aOut["resultID"]=$resultID;
				$ret=pg_cmdtuples($resultID);				$aOut["affectedRows"]=$ret;
				$ret=pg_getlastoid($resultID);			$aOut["lastOID"]=$ret;
				$ret=pg_numrows($resultID);					$aOut["nbRows"]=$ret;
				$ret=pg_numfields($resultID);				$aOut["nbCols"]=$ret;
				break;


			case "OCI":
				$resultID=ociparse($handle, $sql);
				if ($resultID===false)
					return "Error: ociparse sql=".$sql;
				$res=ociexecute($resultID, OCI_DEFAULT);
				if ($res===false)
					return "Error: ociexecute sql=".$sql;

				$aResultID[]=$resultID;							// push the resultID
																						$aOut["resultID"]=$resultID;
				$ret=OCIRowCount($resultID);				$aOut["affectedRows"]=$ret;
				$ret=OCINumCols($resultID);					$aOut["nbCols"]=$ret;
				$aOut["nbRows"]=0;

				if (!$aOut["affectedRows"])
				{ // pas de ligne modifiées: compte le nombre de lignes renvoyées
					$toto=array();
					if (@@ociFetchStatement($resultID, &$toto, 0,-1,OCI_FETCHSTATEMENT_BY_ROW +OCI_RETURN_NULLS))
					{
						$aOut["nbRows"]=count($toto);
						$res=ociexecute($resultID, OCI_DEFAULT);
						if ($res===false)
							return "Error: ociexecute2 sql=".$sql;
					}
					unset($toto);
				}

				break;


			case "MYSQL":
				$resultID=mysql_query($sql, $handle);
				if ($resultID===false)				return 'Error: mysql_query('.mysql_errno($handle).','.mysql_error($handle).') $sql="'.$sql.'"';

				$ret=mysql_affected_rows($handle);	$aOut["affectedRows"]=$ret;
				$ret=mysql_insert_id($handle);			$aOut["lastOID"]=$ret;

				if(is_resource($resultID))
				{	$aResultID[]=$resultID;							// push the resultID
																							$aOut["resultID"]=$resultID;
					$ret=mysql_num_rows($resultID);			$aOut["nbRows"]=$ret;
					$ret=mysql_num_fields($resultID);		$aOut["nbCols"]=$ret;
				}
				break;
		}

																							$aOut["curRow"]=-1;
		$globals["sqlTime"]+=getmicrotime()-$prevtime;
		return "OK";
	}


	function free(&$aResult)
	{
		$prevtime=getmicrotime();
		global $globals;

		$resultID=0;
		if (isset($aResult["resultID"]))
			$resultID=$aResult["resultID"];

		if ($resultID)
		{	switch($this->type)
			{
				case "PGSQL":
					pg_freeresult($resultID);
					break;

				case "OCI":
					OCIFreeStatement($resultID);
					break;

				case "MYSQL":
					mysql_free_result($resultID);
					break;
			}
			$globals["sqlTime"]+=getmicrotime()-$prevtime;
			$aResult["resultID"]=false;
			// enlève le resultID de la table aResultID
			unset($this->aResultID[array_search($resultID, $this->aResultID)]);
		}
	}



	// &$aRow: tableau de destination
	//  $whichRow: "next" pour ligne suivante (défaut), "prev" pour ligne précédente sinon le numéro de ligne

	function fetch_row(&$aResult, $whichRow="next")
	{
		global $globals;
		$globals["sqlNbFetch"]++;
		$prevtime=getmicrotime();

		$aResult["aRow"]=array();
		$numRow=0;
		$resultID=$aResult["resultID"];

		if ($whichRow=="next")
			$numRow=++$aResult["curRow"];
		elseif ($whichRow=="prev" && $aResult["curRow"]>1)
			$numRow=--$aResult["curRow"];
		else
		{	$numRow=$whichRow;
			$aResult["curRow"]=$numRow;
		}

		switch($this->type)
		{
			case "PGSQL":
				$aResult["aRow"]=pg_fetch_row($resultID, $numRow);
				break;

			case "OCI":
				if ($whichRow=="next")
				{
					if (!ociFetchInto($resultID, &$aResult["aRow"], OCI_NUM+OCI_RETURN_NULLS))
						$aResult["aRow"]=array();
				}
				else
					return "error: only next supported with oracle";
				break;

			case "MYSQL":
				if ($whichRow!="next")
					mysql_data_seek($resultID, $numRow);
				$aResult["aRow"]=mysql_fetch_row($resultID);
				break;
		}

		$globals["sqlTime"]+=getmicrotime()-$prevtime;
		if ($aResult["aRow"]==false)
		  return false;
		return true;
	}


	function fetch_array(&$aResult, $whichRow="next")
	{
		global $globals;
		$globals["sqlNbFetch"]++;
		$prevtime=getmicrotime();

		$aResult["aRow"]=array();
		$numRow=0;
		$resultID=$aResult["resultID"];

		if ($whichRow=="next")
			$numRow=++$aResult["curRow"];
		elseif ($whichRow=="prev" && $aResult["curRow"]>1)
			$numRow=--$aResult["curRow"];
		else
		{	$numRow=$whichRow;
			$aResult["curRow"]=$numRow;
		}


		switch($this->type)
		{
			case "PGSQL":
				$aResult["aRow"]=pg_fetch_array($resultID, $numRow, PGSQL_ASSOC);
				break;

			case "OCI":
				if ($whichRow=="next")
					ociFetchInto($resultID, &$aResult["aRow"], OCI_ASSOC+OCI_RETURN_NULLS);
				else
					return "error: next not supported with oracle";
				break;

			case "MYSQL":
				if ($whichRow!="next")
					mysql_data_seek($resultID, $numRow);
				$aResult["aRow"]=mysql_fetch_array($resultID, MYSQL_ASSOC);
				break;
		}

		$globals["sqlTime"]+=getmicrotime()-$prevtime;
		if ($aResult["aRow"]==false)
		  return false;
		return true;
	}


	function get_col_names(&$aResult)
	{
		global $globals;
		$prevtime=getmicrotime();

		$aResult["aCols"]=array();
		$numRow=0;
		$resultID=$aResult["resultID"];

		switch($this->type)
		{
			case "PGSQL":
				$nbCol=pg_numfields($resultID);
				if ($nbCol==-1)
					return "Error: sql_get_field_names:pg_numfields";

				for ($runCol=0; $runCol<$nbCol; $runCol++)
					$aResult["aCols"][$runCol]=pg_fieldname($resultID, $runCol);
				break;

			case "MYSQL":
				$nbCol=mysql_num_fields($resultID);

				for ($runCol=0; $runCol<$nbCol; $runCol++)
					$aResult["aCols"][$runCol]=mysql_field_name($resultID, $runCol);
				break;

			case "OCI":
				return "get_col_names Not supported by OCI.";
		}

		$globals["sqlTime"]+=getmicrotime()-$prevtime;
		return "OK";
	}


  function retrieve_array($sql)
  {	// Récupère le contenu d'une table dans un tableau simple
  	$aOut=array();

  	$sResult=$this->exec($sql, &$rResult);
  	if (check_result($sResult, "sql_exec".$sql, 0)==3)	return false;
  	while($this->fetch_array(&$rResult))
  	{
  	  if (count($rResult["aRow"])==1)
  	  { $a=array_values($rResult["aRow"]);
    		$aOut[]=$a[0];
    	}
  	  else
    		$aOut[]=$rResult["aRow"];
  	}
  	$this->free(&$rResult);

  	return $aOut;
  }

  function retrieve_index($sql, $index)
  {	// Récupère le contenu d'une table dans un tableau $aOut[index]
  	$aOut=array();

  	$sResult=$this->exec($sql, &$rResult);
  	if (check_result($sResult, "sql_exec".$sql, 0)==3)	return false;
  	while($this->fetch_array(&$rResult))
  	{	$key=$rResult["aRow"][$index];
  		unset($rResult["aRow"][$index]);
  		if (count($rResult["aRow"])==0)
  		  $aOut[$key]=false;
  		else
  		  $aOut[$key]=$rResult["aRow"];
  	}
  	$this->free(&$rResult);

  	return $aOut;
  }

  function retrieve_assoc($sql, $key, $value='')
  {	// Récupère le contenu d'une table dans un tableau $aOut[$key]=$value
  	$aOut=array();

  	$sResult=$this->exec($sql, &$rResult);
  	if (check_result($sResult, "sql_exec".$sql, 0)==3)	return false;
  	if (strlen($value))
  	{
  	  while($this->fetch_array(&$rResult))
    		$aOut[$rResult["aRow"][$key]]=$rResult["aRow"][$value];
    }
    else
    {
      while($this->fetch_array(&$rResult))
    		$aOut[$rResult["aRow"][$key]]=false;

    }
  	$this->free(&$rResult);

  	return $aOut;
  }




  /**
   * Quote une chaine au bon format
   *
   * @param string $str
   * @return string
   */
  public function quote($str)
  {
		switch($this->type)
		{
			case "PGSQL":
				return pg_escape_string($this->handle, $str);
			case "OCI":
				return addslashes($str);
			case "MYSQL":
				return mysql_real_escape_string($str, $this->handle);
		}
		throw new Exception("Type de db non géré");
 }


 /**
  * Prépare un ordre SQL en remplaçant les '?' par les arguments donnés (avec quote)
  * Le caractère ? est interdit dans les arguments
  *
  * @param string $sql
  * @param array $args
  * @return string
  */
 public function prepare($sql, array $args)
 {
		foreach ($args as $arg)
		{
			$arg=str_replace("?", "", $arg);	// Interdire les "?" dans les arguments
			$pos=strpos($sql, '?');
			if ($pos!==false)
				$sql=substr_replace($sql, $this->quote($arg), $pos, 1);
		}
 		return $sql;
 }

 	/**
	 * Renvoie toutes les lignes d'un select
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array[optional] $bindargs exemple array("PE2")
	 * @param string[optional] $index Si spécifié, utilise la colonne comme clé
	 * @param string[optional] $col Si spécifié, ne renvoie que cette colonne
	 *
	 * @return array|string
	 * @throws Exception
	 */
	public function retrieve_all($sql, $bindargs=array(), $index="", $col="")
	{
		$sql=$this->prepare($sql, $bindargs);

  	$sResult=$this->exec($sql, &$rResult);
  	if (check_result($sResult, "sql_exec".$sql, 0)==3)
  		throw new Exception($sResult);

		$fCol=(strlen($col)>0);						// True si on veut juste une valeur
		$fIdx=(strlen($index)>0);					// True si on veut indexer

		$ret=array();
		if ($fCol && !$fIdx)
		{	// on veut array[numero]=valeur de la colonne
			while ( $this->fetch_array(&$rResult) )
				$ret[]=$rResult["aRow"][$col];
		}
		elseif ($fCol && $fIdx)
		{	// on veut array[index]=valeur de la colonne
			while ( $this->fetch_array(&$rResult) )
			{
				$key=$rResult["aRow"][$index];
				unset($rResult["aRow"][$index]);
				$ret[$key]=$rResult["aRow"][$col];
			}
		}
		elseif (!$fCol && !$fIdx)
		{	//on veut juste un array[numero]=array(colonnes=>valeur)
			while ( $this->fetch_array(&$rResult) )
				$ret[]=$rResult["aRow"];
		}
		elseif (!$fCol && $fIdx)
		{	//on veut un array[index]=array(colonnes=>valeur)
			while ( $this->fetch_array(&$rResult) )
			{
				$key=$rResult["aRow"][$index];
				unset($rResult["aRow"][$index]);
				$ret[$key]=$rResult["aRow"];
			}
		}

		$this->free(&$rResult);
		return $ret;
	}


	/**
	 * Renvoie la première ligne d'un select
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array[optional] $bindargurment exemple array("PE2")
	 * @param string[optional] $col Si spécifié, renvoie directement la valeur
	 *
	 * @return array|string
	 * @throws Exception
	 */
	public function retrieve_one($sql, $bindargs=array(), $col="")
	{
		$sql=$this->prepare($sql, $bindargs);

  	$sResult=$this->exec($sql, &$rResult);
  	if (check_result($sResult, "sql_exec".$sql, 0)==3)
  		throw new Exception($sResult);

		$fCol=(strlen($col)>0);						// True si on veut juste une valeur

		$ret=array();
		$this->fetch_array(&$rResult);
		if ($fCol)
		{	// on veut juste la valeur
			$ret=$rResult["aRow"][$col];
		}
		else
		{	//on veut un array
			$ret=$rResult["aRow"];
		}

		$this->free(&$rResult);
		return $ret;
	}





}



?>