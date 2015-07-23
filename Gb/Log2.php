<?php
/**
 * Gb_Log2
 *
 * @author Gilles Bouthenot
 */

namespace Gb;

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
} elseif (\_GB_PATH !== dirname(__FILE__).DIRECTORY_SEPARATOR) {
    throw new \Exception("gbphpdb roots mismatch");
}

require_once \_GB_PATH."Exception.php";
require_once \_GB_PATH."String.php";

class Log2
{
    const LOG_EMERG = 11;
    const LOG_ALERT = 10;
    const LOG_CRIT = 9;
    const LOG_ERROR = 8;           // Ecriture bdd NOK
    const LOG_EXCEPTION = 7;       // Ne devrait pas être atteint
    const LOG_WARNING = 6;
    const LOG_NOTICE = 5;          // Génération d'un identifiant
    const LOG_INFO = 4;            // Ecriture bdd OK
    const LOG_DEBUG = 3;
    const LOG_DUMP = 2;            // comme debug mais verbeux
    const LOG_TRACE = 1;           //

    public static $prepend = "";
    public static $append = "";
    public static $aWriters = array();

    protected static $aLevels = array(
        1 => "tr         ",
        2 => "dmp        ",
        3 => "db--       ",
        4 => "nfo--      ",
        5 => "note--     ",
        6 => "warn---    ",
        7 => "exce----   ",
        8 => "error---   ",
        9 => "crit-----  ",
       10 => "alert----- ",
       11 => "emerg------",
       99 => "MAX LVL 99-"
    );

    protected static $installed = false;

    protected static $startTime = null;

   /**
    * Cette classe ne doit pas être instancée
    */
    private function __construct()
    {
    }


    public static function initStartTime()
    {
        if (null === self::$startTime) {
            self::$startTime = microtime(true);
        }
    }


    public static function init()
    {
        self::initStartTime();
    }


    public static function installErrorHandlers()
    {
        if (false === self::$installed) {
            set_error_handler(array(__CLASS__, "errorHandler"));
            set_exception_handler(array(__CLASS__, "exceptionhandler"));
            register_shutdown_function(array(__CLASS__, "shutdownFunction"));
            ini_set("display_errors", false);
            self::$installed = true;
        }
    }





    /**
     * Loggue dans un fichier
     *
     * @param string $sText Message à ecrire
     * @param string[optional] $sFName Fichier dans lequel ecrire, sinon self::getLogFilename
     */
    public static function logFile($sText, $sFName = "")
    {
        if (!is_string($sText)) {
            $sText = self::dump($sText);
        }
        if (strlen($sFName)) {
            $fd = fopen($sFName, "a");
            $sLog = "";

            // padding et limite à 44 caractères
            $sLog = substr(str_pad($sLog, 44), 0, 44);

            if (isset($_SESSION[__CLASS__."_uniqId"])) {
                $sLog .= "uid=" . $_SESSION[__CLASS__ . "_uniqId"] . " ";
            }
            $sLog .= $sText . " ";

            $vd = debug_backtrace();
            if (isset($vd[1])) {
                $vd = $vd[1];
            } else {
                $vd = $vd[0];
            }

            $sLog .= "file:" . substr($vd["file"], -30) . " line:" . $vd["line"] . " in " . $vd["function"];
            $sLog .= self::dump_array($vd["args"], "(%s)");
            $sLog .= "\n";

            fwrite($fd, $sLog);
            fclose($fd);
        }//endif (strlen($sFName))
    }



    public static function logException( $exce = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_EXCEPTION, $exce, $o, $traceOffset + 1, $traceDeep); }
    public static function logEmerg(     $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_EMERG,     $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logAlert(     $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_ALERT,     $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logCrit(      $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_CRIT,      $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logError(     $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_ERROR,     $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logWarning(   $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_WARNING,   $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logNotice(    $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_NOTICE,    $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logInfo(      $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_INFO,      $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logDebug(     $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_DEBUG,     $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logDump(      $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_DUMP,      $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logTrace(     $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log(self::LOG_TRACE,     $text, $o, $traceOffset + 1, $traceDeep); }
    public static function logNN($level, $text = "", $o = null, $traceDeep = 0, $traceOffset = 0) { self::log($level,              $text, $o, $traceOffset + 1, $traceDeep); }



    /**
     * Loggue un message
     *
     * @param integer $level Gb_Log::LOG_DEBUG,INFO,NOTICE,WARNING,ERROR,CRIT,ALERT,EMERG
     * @param string $text message
     * @param mixed[optional] $o object to dump
     * @param integer[optional] $stackOffset offset backtrace (0 par défaut: met la ligne de l'appel de la fonction)
     * @param integer[optinoal] $stackDeep
     */
    public static function log($level = self::LOG_DEBUG, $text = "", $o = null, $stackOffset = 0, $stackDeep = 0)
    {
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $vd = debug_backtrace(0, $stackOffset + 4);
        } else {
            $vd = debug_backtrace(0);
            // keep only the last 2 levels
            for ($i = count($vd); $i > ($stackOffset + 2); $i--) {
                array_pop($vd);
            }
        }

        // throw away the firsts levels
        for ($i = 0; $i < $stackOffset; $i++) {
            array_shift($vd);
        }

        if (isset($text->xdebug_message)) {
            unset($text->xdebug_message);
        }

        self::writelog($level, $text, $o, $vd, $stackDeep);
    }




    /**
     * Fonction privée, appelée aussi par Gb_Log et Gb_Timer (donc doit être public)
     *
     * @param integer $level
     * @param string $text
     * @param mixed[optional] $o
     * @param array[optional] $backtrace backtrace
     * @param integer[optional] $backtraceDeep
     */
    public static function writelog($level, $text, $o = null, $backtrace = null, $backtraceDeep = 0)
    {
        if (!is_string($text)) {
            $text = self::dump($text);
        }
        $texto = "";
        if (isset($o)) {
            if ($o instanceof Exception) {
            } elseif (is_string($o)) {
                $texto = $o;
            } else {
                $texto = self::dump($o);
            }
        }
        $fHasText = false;
        if (strlen($text)+strlen($texto) > 0) {
            $fHasText = true;
        }

        // aContext
        $aContext = array();
        for ($i = 0; is_numeric($backtraceDeep) && ($i < $backtraceDeep); $i++) {
            // level n is the call file/line
            // level n+1 (if available) is the name of the function that has the log order called from
            $args = null;
            $fxname = "";
            if (!isset($backtrace[$i])) {
                continue;
            }
            if (isset($backtrace[$i + 1])) {
                $fxname = $backtrace[$i + 1]["function"];
                if (isset($backtrace[$i + 1]["class"])) {
                    $fxname = $backtrace[$i + 1]["class"].$backtrace[$i + 1]["type"].$fxname;
                }
                $args = $backtrace[$i + 1]["args"];
            }
            $line = $backtrace[$i]["line"];
            $file = $backtrace[$i]["file"];
            //$file=substr($file,-30);

            if (null !== $args) {
                $args = self::dump_array($args, "%s");
            }

            $context = "";
            if (null !== $file || null !== $fxname) {
                if (strlen($fxname)) {
                    $context .= "$fxname($args)";
                }
                if (strlen($file) || strlen($line)) {
                    $context .= " [ ";
                    if (strlen($file)) {
                        $context .=  $file;
                    }
                    if (strlen($line)) {
                        $context .= ":$line";
                    }
                    $context .= " ]";
                }
            }

            if (strlen($context)) {
                $aContext[] = $context;
            }
        }

        // gather informations
        if (isset(self::$aLevels[$level])) {
            $sLevel = self::$aLevels[$level];
        } else {
            // format a string with a fixed length and the level left-padded
            $sLevel = str_pad($level, self::$aLevels[99], "-");
        }
        $shortdate = date("dm His");

        // elapsed time
        $elapsedTime = null;
        if (null !== self::$startTime) {
            $elapsedTime= microtime(true) - self::$startTime;
        }

        // remote_addr (includes forwarded for)
        $remote_addr = null;
        $REMOTE_USER = "";
        $REMOTE_ADDR = "";
        $HTTP_X_FORWARDED_FOR = "";
        if (isset($_SERVER["REMOTE_ADDR"])) {
            $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
        }
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && ($_SERVER["HTTP_X_FORWARDED_FOR"] != $REMOTE_ADDR)) {
            $HTTP_X_FORWARDED_FOR = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        if (strlen($REMOTE_ADDR) || strlen($HTTP_X_FORWARDED_FOR)) {
            $remote_addr = $REMOTE_ADDR;
            if (strlen($HTTP_X_FORWARDED_FOR)) {
                $remote_addr .= "/" . $HTTP_X_FORWARDED_FOR;
            }
        }

        // remote user
        $remote_user = null;
        if (isset($_SERVER["REMOTE_USER"])) {
            $remote_user = $_SERVER["REMOTE_USER"];
        }






        // populate logdata
        $logdata = array(
            "context" => $aContext,
            "dateShort" => date("dm His"),      // "3112 235959"
            "dateIso" => date("Y-m-d H:i:s"),   // "2015-12-31 23:59:59"
            "dateUnix" => time(),               // int
            "level" => $level,                  // 4
            "levelString" => $sLevel,           // "nfo--      "
            "objRaw" => $o,
            "objString" => $texto,
            "remoteAddr" => $remote_addr,      // "172.26.1.2/195.221.254.2"
            "remoteUser" => $remote_user,
            "timeelapsed" => $elapsedTime,
            "text" => $text,
        );




        foreach (self::$aWriters as $writer) {
            $writer->log($logdata);
        }
        return;


        if ($level>=self::$loglevel_file && strlen($logFilename)) {
            // "{{dateShort}} {{remoteAddr}} {{44}} {{levelString}}{{prepend}}{{text}}{{append}} user={{remoteUser}}"

            // padding et limite à 44 caractères
            //$sLog = substr(str_pad($sLog, 44), 0, 44);



            // écrit dans fichier de log
            $sLog = $date;
            $sLog .= $REMOTE_ADDR;

            if (strlen($HTTP_X_FORWARDED_FOR)) {
                $sLog .= "/" . $HTTP_X_FORWARDED_FOR;
            }
            $sLog .= " ";

            // padding et limite à 44 caractères
            $sLog = substr(str_pad($sLog, 44), 0, 44);

            $sLog .= $sLevel . " ";

            $plugins = \Gb_Glue::getPlugins("Gb_Log");
            foreach ($plugins as $plugin) {
                if (is_callable($plugin[0])) {
                    $plug = call_user_func_array($plugin[0], $plugin[1]);
                    if (strlen($plug)) {
                        $sLog .= $plug . " ";
                    }
                }
            }

            if (strlen(self::$prepend)) {
                $text = self::$prepend . $text;
            }
            if (strlen(self::$append)) {
                $text = $text . self::$append;
            }

            if (strlen($REMOTE_USER)) {
                $sLog .= "user={$REMOTE_USER} ";
            }

            $indentLen = strlen($sLog);
            if ($fHasText) {
                if (strlen($text) && strlen($texto)) {
                    $sLog .= "{$text} {$texto}\n";
                } else {
                    $sLog .= "{$text}{$texto}\n";
                }
            }

            foreach ($aContext as $i => $context) {
                if (!(0===$i && !$fHasText)) {
                    $sLog .= str_repeat(" ", $indentLen);
                }
                $sLog .= "[$i] $context\n";
            }

            $fd = @fopen($logFilename, "a");
            if ($fd) {
                fwrite($fd, $sLog);
                fclose($fd);
            }
        }

        if ($level >= self::$loglevel_footer) {
             // écrit dans le footer
            $sLog = "{$sLevel} t+{$elapsedTime}: ";
            $indentLen = strlen($sLog);
            if ($fHasText) {
                $sLog .= "{$text}{$texto}\n";
            }
            foreach ($aContext as $i => $context) {
                if (!(0==$i && !$fHasText)) {
                    $sLog .= str_repeat(" ", $indentLen);
                }
                $sLog .= "[{$i}] {$context}\n";
            }
            \Gb_Response::$footer .= $sLog;
        }

    }



    /**
     * Renvoie une description sur une ligne d'une variable (comme print_r, mais sur une ligne)
     * préférer dump
     *
     * @param mixed $var Variable à dumper
     * @pram string[optional] $sFormat mettre "%s" pour n'avoir que le contenu du array. Par défaut, c'est "array(%s)".
     * @return string
     */
    public static function dump_array($var, $sFormat = "array(%s)")
    {
        if (null === $var) {
            return "null";
        }
        $sLog = "";
        $curnum = 0;
        $fShowKey = false;
        foreach ($var as $num => $arg) {
            if ($curnum) {
                $sLog .= ", ";
            }
            $pr = "";
            if (is_array($arg)) {
                $pr = self::dump_array($arg);
            } else {
                $pr = print_r($arg, true);
                $pr = preg_replace("/^ +/m", "", $pr);        // enlève les espaces en début de ligne
                $pr = preg_replace("/,\n\\)/m", ")", $pr);    // remplace les ,) par )
                $pr = preg_replace("/,$/m", ", ", $pr);       // remplace "," par ", " en fin de ligne
                $pr = str_replace("\n", "", $pr);             // met tout sur une ligne
                $pr = str_replace(" => ", "=>", $pr);         // enlève les espaces avant et après "=>"
                $pr = str_replace("array (", "array( ", $pr); // formate array (
            }
            if ($fShowKey || ($curnum !== $num)) {
                $fShowKey = true;
                $pr = "$num=>$pr";
            }
            $sLog .= $pr;
            $curnum++;
        }
        return sprintf($sFormat, $sLog);
    }



    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (0 === error_reporting()) {
            // @ error-control operator.
            // http://php.net/manual/en/language.operators.errorcontrol.php
            return;
        }
        $aHandled = array(E_WARNING, E_NOTICE, E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED, E_DEPRECATED);
        if (in_array($errno, $aHandled)) {
            // is only a warning
            $text = "warning: $errstr";
            $text .= ", in file $errfile, line $errline";
            self::writelog(self::LOG_WARNING, $text);
            return false;
        } else {
            // is an error
            header("HTTP/1.0 500 Application Error");
            $text = $errstr;
            echo $text;
            $text = "error: $text, in file $errfile, line $errline";
            self::writelog(self::LOG_ERROR, $text);
        }
    }




    public static function shutdownFunction()
    {
        $error = error_get_last();
        $aHandled = array(E_CORE_WARNING, E_COMPILE_WARNING);
        if (null !== $error && in_array($error['type'], $aHandled)) {
            // these warnings cannot be handled by errorHandler
            $errstr = $error["message"];
            $errfile = $error["file"];
            $errline = $error["line"];
            $text = "shutdown warning: $errstr, in file $errfile, line $errline";
            self::writelog(self::LOG_WARNING, $text);
        }
        $aHandled = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR);
        if (null !== $error && in_array($error['type'], $aHandled)) {
            // these errors cannot be handled by errorHandler
            $errstr = $error["message"];
            $errfile = $error["file"];
            $errline = $error["line"];
            header("HTTP/1.0 500 Application Error");
            $text = $errstr;
            echo $text;
            $text = "fatal error: $text, in file $errfile, line $errline";
            self::writelog(self::LOG_ERROR, $text);
        }
    }



    public static function exceptionHandler(Exception $e)
    {
        if (!headers_sent()) {
            header("HTTP/1.0 500 Application Exception");
        }
        echo $e->getMessage();
        self::writelog(self::LOG_ERROR, $e);
    }









    /**
     * Renvoie une description sur une ligne d'une variable (comme print_r, mais sur une ligne)
     *
     * @param mixed $var Variable à dumper
     * @return string
     */
    public static function dump($var)
    {
        if (is_array($var)) {
            $pr = self::dump_array($var);
        } elseif (method_exists($var, "__toString")) {
            $pr = $var->__toString();
        } else {
            $pr = print_r($var, true);
            $pr = preg_replace("/^ +/m", "", $pr);             // enlève les espaces en début de ligne
            $pr = preg_replace("/,\n\\)/m", ")", $pr);         // remplace les ,) par )
            $pr = preg_replace("/,$/m", ", ", $pr);            // remplace "," par ", " en fin de ligne
            $pr = str_replace("\n", "", $pr);                  // met tout sur une ligne
            $pr = str_replace(" => ", "=>", $pr);              // enlève les espaces avant et après "=>"
            $pr = str_replace("array (", "array( ", $pr);      // formate array (
        }
        return $pr;
    }

    /**
     * trim / pad a string
     * only if $len is a integer
     * lensep may be:
     *   "<": trim the string, so its length is <= $len
     *   ">": pad the string with space until length is >= $len
     *   "=": trim/pad the string, force the length to be = $len
     * @param string $value
     * @param int $len
     * @param char $lensep
     * @return string
     */
    protected static function trimpad($value, $len, $lensep)
    {
        if ((int) $len > 0) {
            $len = (int) $len;
            if ("<" === $lensep) {
                $value = substr($value, 0, $len);
            } elseif (">" === $lensep) {
                $value = str_pad($value, $len);
            } elseif ("=" === $lensep) {
                $value = substr($value, 0, $len);
                $value = str_pad($value, $len);
            }
        }
        return $value;
    }

    /**
     * format a string
     * @param array $logdata
     * @param string $format
     */
    public static function formatString(array $logdata, $format)
    {
        $output = $format;
        $matches = null;
        // $pattern = "/{(.*?{(.+?)}.*?)}/";

        $pattern = "~{(?P<subject>.*?(?P<keydst>{((?P<len>[0-9]*)(?P<lensep>[<>=]))?(?P<key>.*?)}).*?)}~";
        $nbMatches = preg_match_all($pattern, $output, $matches);

        for ($i = 0; $i < $nbMatches; $i++) {
            $subsearch = $matches[0][$i];           // "{user={12,remoteUser} }"
            $subject = $matches["subject"][$i];     // "user={12,remoteUser} "
            $keydst = $matches["keydst"][$i];       // "{12,remoteUser}"
            $key = $matches["key"][$i];             // "remoteUser"
            $len = $matches["len"][$i];             // "12"
            $lensep = $matches["lensep"][$i];       // ","

            // compute left and right part of the string so
            // $output = $left . $subsearch . $right
            $pos = strpos($output, $subsearch);
            $left = substr($output, 0, $pos);       // left part of the output string
            $right = substr($output, $pos + strlen($subsearch)); // right part

            $subst = '';
            if ('' === $key) {
                $left = self::trimpad($left, $len, $lensep);
            } elseif (isset($logdata[$key])) {
                $value = $logdata[$key];
                $value = self::trimpad($value, $len, $lensep);
                $subst =  str_replace($keydst, $value, $subject);
            }
            $output = $left . $subst . $right;
        };

        return $output;
    }
}
