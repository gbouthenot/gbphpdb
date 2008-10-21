<?php
/**
 * Gb_Log
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Glue.php");
require_once(_GB_PATH."Util.php");


class Gb_Log
{
    const LOG_EMERG=11;
    const LOG_ALERT=10;
    const LOG_CRIT=9;
    const LOG_ERROR=8;           // Ecriture bdd NOK
    const LOG_EXCEPTION=7;       // Ne devrait pas être atteint
    const LOG_WARNING=6;
    const LOG_NOTICE=5;          // Génération INE
    const LOG_INFO=4;            // Ecriture bdd OK
    const LOG_DEBUG=3;
    const LOG_DUMP=2;            // comme debug mais verbeux
    const LOG_TRACE=1;           // 
    
    const LOG_NONE=99;
    const LOG_ALL=0;

    public static $logFilename="";                  // Fichier de log, par défaut error_log/PROJECTNAME.log
    public static $loglevel_firebug=self::LOG_NONE;
    public static $loglevel_footer=self::LOG_DEBUG;
    public static $loglevel_file=self::LOG_WARNING;
    public static $loglevel_showuser=self::LOG_CRIT;
    
    protected static $aLevels=array(
        1=>"tr         ",
        2=>"dmp        ",
        3=>"db--       ",
        4=>"nfo--      ",
        5=>"note--     ",
        6=>"warn---    ",
        7=>"exce----   ",
        8=>"error---   ",
        9=>"crit-----  ",
       10=>"alert----- ",
       11=>"emerg------"
    );

    /// correspondance pour icone firebug
    protected static $aFBLevels=array(
        1=>"TRACE",
        2=>"DUMP",
        3=>"LOG",
        4=>"INFO",
        5=>"INFO",
        6=>"WARN",
        7=>"EXCEPTION",
        8=>"ERROR",
        9=>"ERROR",
       10=>"ERROR",
       11=>"ERROR"
    );
    
    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision$';
        $revision=trim(substr($revision, strrpos($revision, ":")+2, -1));
        if ($mini===null) { return $revision; }
        if ($revision>=$mini) { return true; }
        if ($throw) { throw new Gb_Exception(__CLASS__." r".$revision."<r".$mini); }
        return false;
    }
        

   /**
    * Cette classe ne doit pas être instancée !
    */
    private function __construct()
    {
    }
    
    
    
    
    /**
     * Renvoie le nom du fichier de log
     *
     * @return string logFilename
     */
    public static function getLogFilename()
    {
        if ( self::$logFilename=="" ) { // met le logFilename sur error_log/{PROJECTNAME}.LOG
            $logFilename=ini_get("error_log");
            $d=addslashes(DIRECTORY_SEPARATOR);;
            // 1: /var/log/php5 2:php_error.log
            unset($matches);
            preg_match("@^(.+$d)(.+)\$@", $logFilename, $matches);
            if (isset($matches[1])) { 
                self::$logFilename=$matches[1].Gb_Glue::getProjectName().".log";
            } else {
                // pas de répertoire: utilise session_save_path
                $updir=Gb_Glue::getOldSessionDir();
                self::$logFilename=$updir.DIRECTORY_SEPARATOR.Gb_Glue::getProjectName().".log";
            }
        }
        return self::$logFilename;
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


  
  public static function logEmerg($text="", $o=null) { self::log(self::LOG_EMERG, $text, $o, 1); }
  public static function logAlert($text="", $o=null) { self::log(self::LOG_ALERT, $text, $o, 1); }
  public static function logCrit($text="", $o=null) { self::log(self::LOG_CRIT, $text, $o, 1); }
  public static function logError($text="", $o=null) { self::log(self::LOG_ERROR, $text, $o, 1); }
  public static function logException($text="", $o=null) { self::log(self::LOG_EXCEPTION, $text, $o, 1); }
  public static function logWarning($text="", $o=null) { self::log(self::LOG_WARNING, $text, $o, 1); }
  public static function logNotice($text="", $o=null) { self::log(self::LOG_NOTICE, $text, $o, 1); }
  public static function logInfo($text="", $o=null) { self::log(self::LOG_INFO, $text, $o, 1); }
  public static function logDebug($text="", $o=null) { self::log(self::LOG_DEBUG, $text, $o, 1); }
  public static function logDump($text="", $o=null) { self::log(self::LOG_DUMP, $text, $o, 1); }
  public static function logTrace($text="", $o=null) { self::log(self::LOG_TRACE, $text, $o, 1); }
  
  
  
    /**
     * Loggue un message
     *
     * @param integer $level Gb_Log::LOG_DEBUG,INFO,NOTICE,WARNING,ERROR,CRIT,ALERT,EMERG
     * @param string  $text message
     * @param mixed   $o object à dumper
     * @param integer[optional] $red offset backtrace (0 par défaut: met la ligne de l'appel de la fonction)
     */
  public static function log($level=self::LOG_DEBUG, $text="", $o=null, $red=0)
  {
        $vd=debug_backtrace();
        $vd0=$vd1=$vd[$red];
        if (isset($vd[$red+1])) {
            $vd1=$vd[$red+1];
        }
        self::writelog($level, $text, $vd0["file"], $vd0["line"], $vd1["function"], "...", null, $o);
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
//    $aParam=array();
//    for ($i=1; $i<func_num_args(); $i++)
//      $aParam[]=func_get_arg($i);
//    $aParam=func_get_args();
//    array_shift($aParam);

    unset($sCallName);
    if (is_callable($fName, false, $sCallName)) // $sCallName reçoit le nom imprimable de la fonction, utile pour les objets
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
     * @param integer $level
     * @param string $text
     * @param string $file
     * @param integer $line
     * @param string $fxname
     * @param array $fxparam
     * @param mixed $fxreturn
     * @param mixed $o
     */
  public static function writelog($level, $text, $file, $line, $fxname="", $fxparam="", $fxreturn="", $o=null)
  {
    $logFilename=self::getLogFilename();
    if (!is_string($text)) {
        $text=self::dump($text);
    }

    $sLevel=self::$aLevels[$level];
    $timecode=microtime(true)-Gb_Glue::getStartTime();
    $timecode=sprintf("%.03f", $timecode);
    $REMOTE_USER="";          if (isset($_SERVER["REMOTE_USER"]))          $REMOTE_USER=         $_SERVER["REMOTE_USER"];
    $REMOTE_ADDR="";          if (isset($_SERVER["REMOTE_ADDR"]))          $REMOTE_ADDR=         $_SERVER["REMOTE_ADDR"];
    $HTTP_X_FORWARDED_FOR=""; if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
    $date=date("dm His ");
    $file=substr($file,-30);

    if ($level>=self::$loglevel_showuser) {
        // montre l'erreur
      echo $text;
    }

    if ($level>=self::$loglevel_firebug) {
        // si pas d'object, met un texte nul et l'objet
        if ($o===null) {
            self::fb("", $level, $text);
        } else {
            self::fb($text, $level, $o);
        }
    }
    
    if ($level>=self::$loglevel_file && strlen($logFilename)) {
        // écrit dans fichier de log
      $sLog=$date;
      $sLog.=$REMOTE_ADDR;

      if (strlen($HTTP_X_FORWARDED_FOR))          $sLog.="/".$HTTP_X_FORWARDED_FOR;
      $sLog.=" ";

      // padding et limite à 40 caractères
      $sLog=substr(str_pad($sLog, 40),0, 40);

      $sLog.=$sLevel." ";

        $plugins=Gb_Glue::getPlugins("Gb_Log");
        foreach ($plugins as $plugin) {
            if (is_callable($plugin[0])) {
                $sLog.=call_user_func_array($plugin[0], $plugin[1]);
                $sLog.=" ";
            }
        }
      
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
    { if ($curnum)      $sLog.=", ";
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
  
  
  ///////////////////////////////////// PARTIE FirePHP ////////////////////////////////////////
  
  
  




/* ***** BEGIN LICENSE BLOCK *****
 *  
 * This file is part of FirePHP (http://www.firephp.org/).
 *
 * Software License Agreement (New BSD License)
 *
 * Copyright (c) 2006-2008, Christoph Dorn
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *
 *     * Neither the name of Christoph Dorn nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * ***** END LICENSE BLOCK ***** */
 
 
/* modifs par rapport à l'original:
 * log() -> fb_log()
 * dump() ->fb_dump()
 * const enlevés
 * $Type = self::LOG;                 --> $Type = self::LOG_DEBUG;
 * $Type = self::EXCEPTION;           --> $Type = self::LOG_EXCEPTION;
 * if($Type==self::DUMP) {            --> if($Type==self::LOG_DUMP) {
 * if($Type==self::DUMP) {            --> if($Type==self::LOG_DUMP) {
 * if($Type==self::TRACE) {           --> if($Type==self::LOG_TRACE) {
 * $this->setHeader('X-FirePHP-Data-'.(($Type==self::DUMP)?'2':'3').$mt, $part);   --> LOG_DUMP
 * call_user_func_array(array($this,'fb'),array($args,FirePHP::LOG));              --> self::LOG_DEBUG
 * call_user_func_array(array($this,'fb'),array($Variable,$Key,FirePHP::DUMP));    --> self::LOG_DUMP
 * 
 */ 
  
  
 
/**
 * Sends the given data to the FirePHP Firefox Extension.
 * The data can be displayed in the Firebug Console or in the
 * "Server" request tab.
 *
 * For more informtion see: http://www.firephp.org/
 *
 * @copyright   Copyright (C) 2007-2008 Christoph Dorn
 * @author      Christoph Dorn <christoph@christophdorn.com>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 */

 
  public static function setProcessorUrl($URL)
  {
    self::setHeader('X-FirePHP-ProcessorURL', $URL);
  }

  public static function setRendererUrl($URL)
  {
    self::setHeader('X-FirePHP-RendererURL', $URL);
  }
 

  public static function detectClientExtension() {
    /* Check if FirePHP is installed on client */
    if(!@preg_match_all('/\sFirePHP\/([\.|\d]*)\s?/si',self::getUserAgent(),$m) ||
       !version_compare($m[1][0],'0.0.6','>=')) {
      return false;
    }
    return true;    
  }
 
  public static function fb($label, $Type=self::LOG_DEBUG, $Object) {
 
    if (headers_sent($filename, $linenum)) {
        throw self::newException('Headers already sent in '.$filename.' on line '.$linenum.'. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.');
    }
 
    if(!self::detectClientExtension()) {
      return false;
    }
 
    if($Object instanceof Exception) {
     
      $Object = array('Class'=>get_class($Object),
                      'Message'=>$Object->getMessage(),
                      'File'=>self::_escapeTraceFile($Object->getFile()),
                      'Line'=>$Object->getLine(),
                      'Type'=>'throw',
                      'Trace'=>self::_escapeTrace($Object->getTrace()));
      $Type = self::LOG_EXCEPTION;
     
    } else
    if($Type==self::LOG_TRACE) {
     
      $trace = debug_backtrace();
      if(!$trace) return false;
      for( $i=0 ; $i<sizeof($trace) ; $i++ ) {
       
        if($trace[$i]['class']=='FirePHP' &&
           substr(self::_standardizePath($trace[$i+1]['file']),-18,18)=='FirePHPCore/fb.php') {
          /* Skip */
        } else
        if($trace[$i]['function']=='fb') {
          $Object = array('Class'=>$trace[$i]['class'],
                          'Type'=>$trace[$i]['type'],
                          'Function'=>$trace[$i]['function'],
                          'Message'=>$trace[$i]['args'][0],
                          'File'=>self::_escapeTraceFile($trace[$i]['file']),
                          'Line'=>$trace[$i]['line'],
                          'Args'=>$trace[$i]['args'],
                          'Trace'=>self::_escapeTrace(array_splice($trace,$i+1)));
          break;
        }
      }
    }
 
    self::setHeader('X-FirePHP-Data-100000000001','{');
    if($Type==self::LOG_DUMP) {
        self::setHeader('X-FirePHP-Data-200000000001','"FirePHP.Dump":{');
        self::setHeader('X-FirePHP-Data-299999999999','"__SKIP__":"__SKIP__"},');
    } else {
        self::setHeader('X-FirePHP-Data-300000000001','"FirePHP.Firebug.Console":[');
        self::setHeader('X-FirePHP-Data-399999999999','["__SKIP__"]],');
    }
        self::setHeader('X-FirePHP-Data-999999999999','"__SKIP__":"__SKIP__"}');
 
    if($Type==self::LOG_DUMP) {
        $msg = '"'.$Object[0].'":'.json_encode($Object[1]).',';
    } else {
        $msg = '["'. self::$aFBLevels[$Type] .'",'.json_encode($Object).'],';
    }
   
    foreach( explode("\n",chunk_split($msg, 5000, "\n")) as $part ) {
         
      if($part) {


        usleep(1); /* Ensure microtime() increments with each loop. Not very elegant but it works */
   
                $mt = explode(' ',microtime());
                $mt = substr($mt[1],7).substr($mt[0],2);
   
        self::setHeader('X-FirePHP-Data-'.(($Type==self::LOG_DUMP)?'2':'3').$mt, $part);
      }
        }
   
    return true;
  }
 
  protected static function _standardizePath($Path) {
    return preg_replace('/\\\\+/','/',$Path);    
  }
 
  protected static function _escapeTrace($Trace) {
    if(!$Trace) return $Trace;
    for( $i=0 ; $i<sizeof($Trace) ; $i++ ) {
        if (isset($Trace[$i]['file'])) {    // GILLES
            $Trace[$i]['file'] = self::_escapeTraceFile($Trace[$i]['file']);
        }
    }
    return $Trace;    
  }
 
  protected static function _escapeTraceFile($File) {
    /* Check if we have a windows filepath */
    if(strpos($File,'\\')) {
      /* First strip down to single \ */
     
      $file = preg_replace('/\\\\+/','\\',$File);
     
      return $file;
    }
    return $File;
  }

  protected static function setHeader($Name, $Value) {
    return header($Name.': '.$Value);
  }

  protected static function getUserAgent() {
    if(!isset($_SERVER['HTTP_USER_AGENT'])) return false;
    return $_SERVER['HTTP_USER_AGENT'];
  }

  protected static function newException($Message) {
    return new Exception($Message);
  }

}

class dummy
{
   
  /**
   * Converts to and from JSON format.
   *
   * JSON (JavaScript Object Notation) is a lightweight data-interchange
   * format. It is easy for humans to read and write. It is easy for machines
   * to parse and generate. It is based on a subset of the JavaScript
   * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
   * This feature can also be found in  Python. JSON is a text format that is
   * completely language independent but uses conventions that are familiar
   * to programmers of the C-family of languages, including C, C++, C#, Java,
   * JavaScript, Perl, TCL, and many others. These properties make JSON an
   * ideal data-interchange language.
   *
   * This package provides a simple encoder and decoder for JSON notation. It
   * is intended for use with client-side Javascript applications that make
   * use of HTTPRequest to perform server communication functions - data can
   * be encoded into JSON notation for use in a client-side javascript, or
   * decoded from incoming Javascript requests. JSON format is native to
   * Javascript, and can be directly eval()'ed with no further parsing
   * overhead
   *
   * All strings should be in ASCII or UTF-8 format!
   *
   * LICENSE: Redistribution and use in source and binary forms, with or
   * without modification, are permitted provided that the following
   * conditions are met: Redistributions of source code must retain the
   * above copyright notice, this list of conditions and the following
   * disclaimer. Redistributions in binary form must reproduce the above
   * copyright notice, this list of conditions and the following disclaimer
   * in the documentation and/or other materials provided with the
   * distribution.
   *
   * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
   * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
   * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
   * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
   * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
   * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
   * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
   * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
   * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
   * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
   * DAMAGE.
   *
   * @category
   * @package     Services_JSON
   * @author      Michal Migurski <mike-json@teczno.com>
   * @author      Matt Knapp <mdknapp[at]gmail[dot]com>
   * @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
   * @author      Christoph Dorn <christoph@christophdorn.com>
   * @copyright   2005 Michal Migurski
   * @version     CVS: $Id$
   * @license     http://www.opensource.org/licenses/bsd-license.php
   * @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
   */
   
     
  /**
   * Keep a list of objects as we descend into the array so we can detect recursion.
   */
  private static $json_objectStack = array();


 /**
  * convert a string from one UTF-8 char to one UTF-16 char
  *
  * Normally should be handled by mb_convert_encoding, but
  * provides a slower PHP-only method for installations
  * that lack the multibye string extension.
  *
  * @param    string  $utf8   UTF-8 character
  * @return   string  UTF-16 character
  * @access   private
  */
  private static function json_utf82utf16($utf8)
  {
      // oh please oh please oh please oh please oh please
      if(function_exists('mb_convert_encoding')) {
          return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
      }

      switch(strlen($utf8)) {
          case 1:
              // this case should never be reached, because we are in ASCII range
              // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              return $utf8;

          case 2:
              // return a UTF-16 character from a 2-byte UTF-8 char
              // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              return chr(0x07 & (ord($utf8{0}) >> 2))
                   . chr((0xC0 & (ord($utf8{0}) << 6))
                       | (0x3F & ord($utf8{1})));

          case 3:
              // return a UTF-16 character from a 3-byte UTF-8 char
              // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              return chr((0xF0 & (ord($utf8{0}) << 4))
                       | (0x0F & (ord($utf8{1}) >> 2)))
                   . chr((0xC0 & (ord($utf8{1}) << 6))
                       | (0x7F & ord($utf8{2})));
      }

      // ignoring UTF-32 for now, sorry
      return '';
  }

 /**
  * encodes an arbitrary variable into JSON format
  *
  * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
  *                           see argument 1 to Services_JSON() above for array-parsing behavior.
  *                           if var is a strng, note that encode() always expects it
  *                           to be in ASCII or UTF-8 format!
  *
  * @return   mixed   JSON string representation of input var or an error if a problem occurs
  * @access   public
  */
  public static function json_encode($var)
  {
   
    if(is_object($var)) {
      if(in_array($var,self::$json_objectStack)) {
        return '"** Recursion **"';
      }
    }
         
      switch (gettype($var)) {
          case 'boolean':
              return $var ? 'true' : 'false';

          case 'NULL':
              return 'null';

          case 'integer':
              return (int) $var;

          case 'double':
          case 'float':
              return (float) $var;

          case 'string':
              // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
              $ascii = '';
              $strlen_var = strlen($var);

             /*
              * Iterate over every character in the string,
              * escaping with a slash or encoding to UTF-8 where necessary
              */
              for ($c = 0; $c < $strlen_var; ++$c) {


                  $ord_var_c = ord($var{$c});

                  switch (true) {
                      case $ord_var_c == 0x08:
                          $ascii .= '\b';
                          break;
                      case $ord_var_c == 0x09:
                          $ascii .= '\t';
                          break;
                      case $ord_var_c == 0x0A:
                          $ascii .= '\n';
                          break;
                      case $ord_var_c == 0x0C:
                          $ascii .= '\f';
                          break;
                      case $ord_var_c == 0x0D:
                          $ascii .= '\r';
                          break;

                      case $ord_var_c == 0x22:
                      case $ord_var_c == 0x2F:
                      case $ord_var_c == 0x5C:
                          // double quote, slash, slosh
                          $ascii .= '\\'.$var{$c};
                          break;

                      case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                          // characters U-00000000 - U-0000007F (same as ASCII)
                          $ascii .= $var{$c};
                          break;

                      case (($ord_var_c & 0xE0) == 0xC0):
                          // characters U-00000080 - U-000007FF, mask 110XXXXX
                          // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                          $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                          $c += 1;
                          $utf16 = self::json_utf82utf16($char);
                          $ascii .= sprintf('\u%04s', bin2hex($utf16));
                          break;

                      case (($ord_var_c & 0xF0) == 0xE0):
                          // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                          // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                          $char = pack('C*', $ord_var_c,
                                       ord($var{$c + 1}),
                                       ord($var{$c + 2}));
                          $c += 2;
                          $utf16 = self::json_utf82utf16($char);
                          $ascii .= sprintf('\u%04s', bin2hex($utf16));
                          break;

                      case (($ord_var_c & 0xF8) == 0xF0):
                          // characters U-00010000 - U-001FFFFF, mask 11110XXX
                          // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                          $char = pack('C*', $ord_var_c,
                                       ord($var{$c + 1}),
                                       ord($var{$c + 2}),
                                       ord($var{$c + 3}));
                          $c += 3;
                          $utf16 = self::json_utf82utf16($char);
                          $ascii .= sprintf('\u%04s', bin2hex($utf16));
                          break;

                      case (($ord_var_c & 0xFC) == 0xF8):
                          // characters U-00200000 - U-03FFFFFF, mask 111110XX
                          // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                          $char = pack('C*', $ord_var_c,
                                       ord($var{$c + 1}),
                                       ord($var{$c + 2}),
                                       ord($var{$c + 3}),
                                       ord($var{$c + 4}));
                          $c += 4;
                          $utf16 = self::json_utf82utf16($char);
                          $ascii .= sprintf('\u%04s', bin2hex($utf16));
                          break;

                      case (($ord_var_c & 0xFE) == 0xFC):
                          // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                          // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                          $char = pack('C*', $ord_var_c,
                                       ord($var{$c + 1}),
                                       ord($var{$c + 2}),
                                       ord($var{$c + 3}),
                                       ord($var{$c + 4}),
                                       ord($var{$c + 5}));
                          $c += 5;
                          $utf16 = self::json_utf82utf16($char);
                          $ascii .= sprintf('\u%04s', bin2hex($utf16));
                          break;
                  }
              }

              return '"'.$ascii.'"';

          case 'array':
             /*
              * As per JSON spec if any array key is not an integer
              * we must treat the the whole array as an object. We
              * also try to catch a sparsely populated associative
              * array with numeric keys here because some JS engines
              * will create an array with empty indexes up to
              * max_index which can cause memory issues and because
              * the keys, which may be relevant, will be remapped
              * otherwise.
              *
              * As per the ECMA and JSON specification an object may
              * have any string as a property. Unfortunately due to
              * a hole in the ECMA specification if the key is a
              * ECMA reserved word or starts with a digit the
              * parameter is only accessible using ECMAScript's
              * bracket notation.
              */

              // treat as a JSON object
              if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                 
                  self::$json_objectStack[] = $var;

                  $properties = array_map(array(__CLASS__, 'json_name_value'),
                                          array_keys($var),
                                          array_values($var));

                  array_pop(self::$json_objectStack);

                  foreach($properties as $property) {
                      if($property instanceof Exception) {
                          return $property;
                      }
                  }

                  return '{' . join(',', $properties) . '}';
              }

              self::$json_objectStack[] = $var;

              // treat it like a regular array
              $elements = array_map(array(__CLASS__, 'json_encode'), $var);

              array_pop(self::$json_objectStack);

              foreach($elements as $element) {
                  if($element instanceof Exception) {
                      return $element;
                  }
              }

              return '[' . join(',', $elements) . ']';

          case 'object':
              $vars = get_object_vars($var);

              self::$json_objectStack[] = $var;

              $properties = array_map(array(__CLASS__, 'json_name_value'),
                                      array_keys($vars),
                                      array_values($vars));

              array_pop(self::$json_objectStack);
             
              foreach($properties as $property) {
                  if($property instanceof Exception) {
                      return $property;
                  }
              }

              return '{'.self::json_encode('__className') . ':' . self::json_encode(get_class($var)) .
                     (($properties)?',':'') .
                     join(',', $properties) . '}';

          default:
              return null;
      }
  }

 /**
  * array-walking function for use in generating JSON-formatted name-value pairs
  *
  * @param    string  $name   name of key to use
  * @param    mixed   $value  reference to an array element to be encoded
  *
  * @return   string  JSON-formatted name-value pair, like '"name":value'
  * @access   private
  */
  private static function json_name_value($name, $value)
  {
      $encoded_value = self::json_encode($value);

      if($encoded_value instanceof Exception) {
          return $encoded_value;
      }

      return self::json_encode(strval($name)) . ':' . $encoded_value;
  }

}

