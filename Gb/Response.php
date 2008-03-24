<?php
/**
 */
if (!defined("_GB_PATH")) {
  define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Request.php");
require_once(_GB_PATH."Util.php");


class Gb_Response
{
  public static $footer="";       // Le footer
  public static $show_footer=0;           // ne pas afficher le footer par défaut
  public static $preventGzip=0;           // compresse en gzip
  public static $noFooterEscape=0;    // Evite la ligne </div></span>, etc...
  public static $nologo=0;                // ne pas afficher "built with gbpgpdb vxxx"
  public static $head=array(
     "title"              =>""
    ,"description"        =>array("name", "")
    ,"keywords"           =>array("name", "")
    ,"author"             =>array("name", "")
    ,"copyright"          =>array("name", "")
    ,"x-URL"              =>array("name", "")
    ,"x-scriptVersion"    =>array("name", Gb_Util::Gb_UtilVERSION)
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

    // pour send_headers()
    const P_HTTP=0;                   // headers HTTP
    const P_CUSTOM=1;                 // autre (pour gestion complete)
    const P_HTML=2;                   // dans la balise <HTML>
    const P_HEAD=3;                   // dans la balise <HEAD>
    const P_XHEAD=4;                  // après la balise </HEAD>
    const P_BODY=5;                   // dans la balise <BODY>
    const P_XBODY=6;                  // après la balise </BODY>
    const P_XHTML=7;                  // après la balise </HTML>
    public static $html_parse=self::P_HTTP;

    public static function send_headers($fPrint=1)
  {
    $head=self::$head;

    $ret="";
    $glo_parse=self::$html_parse;

    if ($glo_parse!=self::P_CUSTOM)
    {
      if ($glo_parse<self::P_HTML)
      {
        if (strlen($head["Expires"][1])==0) {             header("Cache-Control: no-cache, must-revalidate");     // HTTP/1.1
                                                          header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");       // Date du passé
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
        { switch (strtolower($key))
          {        case "title":         $ret.="<title>".$val."</title>\n";
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
   * Cette classe ne doit pas être instancée !
   */
  private function __construct()
  {
  }
  

  /**
   * Envoie le footer
   */
  public static function send_footer()
  {
    $totaltime=microtime(true)-Gb_Util::$starttime;

    self::$footer=htmlspecialchars(self::$footer, ENT_QUOTES);
    self::$footer.=sprintf("Total time: %s s ", Gb_Util::roundCeil($totaltime));

    if (class_exists("Gb_Db")) {
      $sqltime=Gb_Db::get_sqlTime();
      $dbpeak=Gb_Db::get_nbInstance_peak();
      $dbtotal=Gb_Db::get_nbInstance_total();
      $nbrequest=Gb_Db::get_nbRequests();
      if ($sqltime>0) {
        $sqlpercent=$sqltime*100/$totaltime;
        self::$footer.=sprintf("(%.2f%% sql) ", $sqlpercent);
      }
      if ($dbtotal>0) {
        self::$footer.="Gb_Db:{total:$dbtotal peak:$dbpeak nbrequests:$nbrequest} ";
      }
      
    }

    if ( class_exists("Gb_Timer") ) {
      $timetotal=Gb_Timer::get_nbInstance_total();
      if ($timetotal) {
        $timepeak=Gb_Timer::get_nbInstance_peak();
        self::$footer.="Gb_Timer:{total:$timetotal peak:$timepeak} ";
      }
    }

        if ( class_exists("Gb_Cache") ) {
            $nbtotal=Gb_Cache::get_nbTotal();
            $nbcachehits=Gb_Cache::get_nbCacheHits();
            $nbcachemiss=Gb_Cache::get_nbCacheMiss();
            if ($nbtotal) {
                self::$footer.="Gb_Cache:{total:$nbtotal hits:$nbcachehits miss:$nbcachemiss} ";
            }
        }
    
    self::$footer.="\n";

    if (!self::$noFooterEscape)
      echo "</span></span></span></div></div></div></div></div></p>";
    printf("\n<div class='Gb_footer'>\n%s</div>\n", self::$footer);
  }

    /**
     * Ferme les tags body, html
     */
  public static function close_page()
    {
      $hp=self::$html_parse;
    if ($hp>=self::P_HTML && !self::$nologo)
      printf("<!-- built with Gb_Util v%s -->\n", Gb_Util::Gb_UtilVERSION);
    elseif (!self::$nologo)
      printf("built with Gb_Util v%s\n", Gb_Util::Gb_UtilVERSION);

    if ($hp>=self::P_BODY && $hp<self::P_XBODY)
      print "</body>\n";
    if ($hp>=self::P_HTML && $hp<self::P_XHTML)
      print "</html>\n";

    self::$html_parse=self::P_XHTML;
  }
  
    
}
