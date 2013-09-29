<?php
/**
 * Gb_String
 *
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== dirname(__FILE__).DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}

require_once(_GB_PATH."Exception.php");


class Gb_String
{

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision$';
        $revision=(int) trim(substr($revision, strrpos($revision, ":")+2, -1));
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
     * returns unix timestamp number of seconds since 1970-01-01 warning:see mktime() (limit to year 2038)
     * $sTime must be formatted as"dd/mm/yyyyy[ hh:mm:ss[.xxx]]" or "yyyy-mm-dd[ hh:mm:ss[.xxxxxx]]"
     *
     * @param string[optional] $sTime
     * @return integer
     * @throws Gb_Exception
     */
    public static function str_to_time($sTime="")
    {
        $sTime=self::date_fr($sTime);
        if ( strlen($sTime)==23 )
            $sTime=substr($sTime, 0, 19);
        if ( strlen($sTime)>=26 )
            $sTime=substr($sTime, 0, 19);
        if ( strlen($sTime)==10 )
            $sTime .= " 00:00:00";
        if ( strlen($sTime)!=19 )
            throw new Gb_Exception("Error: bad time string:".$sTime);
        $aCTime1=array();
        $aCTimeDate=array();
        $aCTimeTime=array();
        $aCTime1=explode(' ', $sTime);
        if (count($aCTime1)!=2) {
            throw new Gb_Exception("Error: bad time string:".$sTime);
        }
        $aCTimeDate=explode('/', $aCTime1[0]);
        $aCTimeTime=explode(':', $aCTime1[1]);
        if (count($aCTimeDate)!=3 || count($aCTimeTime)!=3) {
            throw new Gb_Exception("Error: bad time string:".$sTime);
        }
        $outTime=mktime($aCTimeTime[0], $aCTimeTime[1], $aCTimeTime[2], $aCTimeDate[1], $aCTimeDate[0], $aCTimeDate[2]);
        return $outTime;
    }

    /**
     * Génère une chaine aléatoire
     * @param integer $nb
     * @param string $alphabet
     * @return string
     */
    public static function random($nb, $alphabet)
    {
        $ret="";
        $alphalen=strlen($alphabet);
        for ($i=0; $i<$nb; $i++) {
            $rand = mt_rand(0, $alphalen-1);
            $car  = substr($alphabet, $rand, 1);
            $ret .= $car;
        }
        return $ret;
    }

    /**
     * Renvoie les premiers mots de la chaine jusqu'à un minimum de lettres
     *
     * @param string $prenoms
     * @param integer $lmin
     * @return string
     */
    public static function create_nom($prenoms, $lmin=4)
    {
        trim($prenoms);
        $out="";
        for ($i=0; $i<strlen($prenoms); $i++ ) {
            $c=substr($prenoms, $i, 1);
            if ( $c==" "&&$i>=$lmin ) {
                break;
            }
            $out.=$c;
        }
        return $out;
    }

    /**
     * conversion en majuscule, charset ASCII, enlève les accents
     *
     * @param string $s
     * @return string
     */
    public static function mystrtoupper($s)
    {
        return strtoupper(self::removeAccents($s));
    }

    /**
     * convert to pure ASCII, (remove accents)
     *
     * @param string $s
     * @return string
     */
    public static function removeAccents($s)
    {
        // convert the string to UTF-8
        $source = "UTF-8";
        if (function_exists("mb_convert_encoding")) {
            $source=mb_detect_encoding($s, array("UTF-8", "ISO-8859-1"));
            if ($source !== "UTF-8") {
                $s=mb_convert_encoding($s, "UTF-8", $source);
            }
        }
        //$s = iconv($source, "ascii//TRANSLIT//IGNORE", $s); // not supported everywhere (ok on linux, nok on windows libiconv1.9)

        //$s = mb_convert_encoding($s, 'ascii', 'UTF-8');
        //setlocale(LC_COLLATE, 'fr_FR.UTF-8');
        //$s = libiconv($source, "ascii//TRANSLIT", $s);            // transform "é" to "'e" or "e" !!

        // function from http://www.weirdog.com/blog/php/supprimer-les-accents-des-caracteres-accentues.html
        // htmlentities it
        $s = htmlentities($s, ENT_NOQUOTES, "UTF-8");


        $s = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $s);
        $s = str_replace("&euro;", "EUR", $s);
        $s = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $s); // pour les ligatures e.g. '&oelig;'
        $s = html_entity_decode($s, ENT_NOQUOTES, "UTF-8");
//        $s = preg_replace('#&[^;]+;#', '', $s); // supprime les autres caractères

        // convert it back to the original charset
        if ($source !== "UTF-8") {
                $s=mb_convert_encoding($s, $source, "UTF-8");
        }

        return $s;
    }


  /**
   * converti, si nécessaire une date au format YYYY-MM-DD en DD/MM/YYYY
   *
   * @param string|integer[optional] $d date à convertir ou "" si date courante
   * @return string
   */
    public static function date_fr($d="")
    {
        if (strlen($d)==0) {
            $d=date("d/m/Y H:i:s");
        }
        elseif (is_int($d)) {
            $d=date("d/m/Y H:i:s", $d);
        }
        elseif (substr($d,4,1)=='-') {
            // date au format YYYY-MM-DD
            $split = explode("-",$d);
            if (count($split) == 3) {
                list($y,$m,$d) = $split;
                $d=substr($d,0,2).'/'.$m.'/'.$y.substr($d,2);
            }
        }

        return $d;
    }

  /**
   * converti, si nécessaire une date au format DD/MM/YYYY en YYYY-MM-DD
   *
   * @param string|int[optional] $d date à convertir ou "" si date courante
   * @return string
   */
    public static function date_iso($d="")
    {
        if (strlen($d)==0) {
            $d=date("Y-m-d H:i:s");
        }
        elseif (is_int($d)) {
            $d=date("Y-m-d H:i:s", $d);
        }
        elseif (substr($d,5,1)=='/') {
            // date au format DD/MM/YYYY
            list($d,$m,$y)=explode('/',$d);
            $d=substr($y,0,4).'-'.$m.'-'.$d.substr($y,4);
        }
        return $d;
    }

    /**
     * Check if a [given] time is in the interval [start; end[ warning, see mktime() (limit to year 2038)
     * @param mixed[optional] $start integer or string (fr/iso)
     * @param mixed[optional] $end integer or string (fr/iso)
     * @param mixed[optional] $time time to test (default: current)
     * @return integer -1/0/+1 means before/in/after
     */
    public static function date_isIntoInterval($start=null, $end=null, $time=null)
    {
        if (null === $time) {
            $time = time();
        } elseif (!is_numeric($time)) {
            $time = self::str_to_time($time);
        }

        if (($start !== null) && (!is_numeric($start))) {
            $start = Gb_String::str_to_time($start);
        }
        if (($end !== null) && (!is_numeric($end))) {
            $end = Gb_String::str_to_time($end);
        }

        if (($start !== null) && ($time < $start)) {
            return -1;
        } elseif (($end !== null) && ($time >= $end)) {
            return +1;
        } else {
            return 0;
        }
    }

    /**
     * as php's explode but returns array() instead of array("") if the input string is empty
     *
     * @param string $delimiter
     * @param string $string
     * @param integer[optional] $limit
     * @return array
     */
    public static function explode($delimiter, $string, $limit=null)
    {
        if ($limit===null) {
            $exp=explode($delimiter, $string);
        } else {
            $exp=explode($delimiter, $string, $limit);
        }
        if (count($exp)==1 && $exp[0]==="") {
            return array();
        }
        return $exp;
    }


    /**
     * Transform an array into CSV format. Replace newlines by " - ".
     * Decodes UTF8 unless $fRawMode is set.
     *
     * @param array   $data        array(array("field"=>$value, ...), ...)
     * @param boolean $fEnableUtf8 set to true for sending UTF8 (default:false:do utf8_decode)
     * @return string
     */
    public static function arrayToCsv(array $data, $fEnableUtf8=null)
    {
        if (count($data)==0) {
            return "";
        }
        $ret="";

        // 1ère ligne du csv: les entêtes
        $firstligne = array_keys($data);
        $firstligne = $firstligne[0];
        $firstligne=$data[$firstligne];
        foreach(array_keys($firstligne) as $ind) {
            $ind = str_replace('"',    '""',   $ind);
            $ret .= "\"".$ind."\";";
        }
        $ret.="\n";

        foreach($data as $ligne) {
            foreach(array_keys($firstligne) as $ind) {
                $col = $ligne[$ind];
                $col = str_replace('"',    '""',   $col);
                $col = str_replace("\r",   "\n",   $col);
                $col = str_replace("\n\n", "\n",   $col);
                $col = str_replace("\n\n", "\n",   $col);
                $col = str_replace("\n\n", "\n",   $col);
                $col = str_replace("\n\n", "\n",   $col);
                $col = str_replace("\n",   " - ",     $col);
                $ret .= "\"".$col."\";";
            }
            $ret.="\n";
        }

        if (true === $fEnableUtf8) {
            return $ret;
        } else {
            return utf8_decode($ret);
        }
    }

    /**
     * Format a number of seconds in day min sec
     * @return string ie "21d 11h 22m 33s"
     */
    public static function formatTime($time)
    {
        $days=    floor($time/86400);
        if ($days) { $time -= $days*86400; }

        $hours=   floor($time/3600);
        if ($hours) { $time -= $hours*3600; }

        $minutes= floor($time/60);
        if ($minutes) { $time -= $minutes*60; }

        $seconds= $time;

        $time_format  = "";
        $time_format .= ($days)?($days."d "):("");
        $time_format .= ($days||$hours)?($hours."h "):("");
        $time_format .= (($hours||$minutes)&&(!$days))?($minutes."m "):("");
        $time_format .= ((!$days)&&(!$hours))?($seconds."s "):("");
        return trim($time_format);
    }

    /**
     * Format a number of bytes in Kib/Mib/Gib/Tib
     * @return string
     */
    public static function formatSize($bytestotal)
    {
        if ($bytestotal>1099511627776) {
            $size_format=number_format($bytestotal/1099511627776, 3)." TiB";
        } elseif ($bytestotal>1073741824) {
            $size_format=number_format($bytestotal/1073741824, 3)." GiB";
        } elseif ($bytestotal>1048576) {
            $size_format=number_format($bytestotal/1048576, 3)." MiB";
        } elseif ($bytestotal>1024) {
            $size_format=number_format($bytestotal/1024, 3)." KiB";
        } else {
            $size_format=number_format($bytestotal, 0)." B";
        }
        return $size_format;
    }

    /**
     * Format an array
     *
     * @param array $array
     * @param string $format[optional] text(default)|html|csv
     * @param integer[optional] maxColLen default:40, 0 for no limit
     * @param string[optional] string to use for padding (default " ", set to "" for no padding)
     * @return string
     */
    public static function formatTable(array $array, $format=null, $maxColLen=null, $pad=null)
    {
        if (null === $format) { $format = "text"; }
        $format=strtolower($format);

        if ('csv' === $format) {
            return self::arrayToCsv($array);
        }

        $ret="";
        if (count($array)==0) {
            return "";
        }
        if (null === $maxColLen) {
            $maxColLen = 40;
        }
        if (null === $pad) {
            $pad = " ";
        }

        // should we also display the key index ?
        $fShowIndex=false;
        $firstkeys=array_keys($array);
        $firstkey=$firstkeys[0];
        if ($firstkey !== 0) {
            $fShowIndex=true;
        }

        if ($format=="text") {
            //
            // COMPUTE WIDTHS
            //
            reset($array);
            $firstrowkeys=array_keys(current($array));

            // get the max length of each column
            $max=array();
            // first the column names
            $max["index"] = mb_strlen("index", "UTF-8");
            foreach ($firstrowkeys as $number=>$keyname) {
                $max[$number] = mb_strlen($keyname, "UTF-8");
                if ($maxColLen) {
                    $max[$number] = min($max[$number], $maxColLen);
                }
            }

            // then the column values
            foreach ($array as $indexname=>$line) {
                $max["index"]=max($max["index"], mb_strlen($indexname, "UTF-8"));
                foreach ($firstrowkeys as $number=>$keyname) {
                    $max[$number]=max($max[$number], mb_strlen(str_replace(array("\r","\n","\0"), array("\\r", "\\n", "\\0"), $line[$keyname]), "UTF-8"));
                    if ($maxColLen) {
                        $max[$number] = min($max[$number], $maxColLen);
                    }
                }
            }

            if ($maxColLen) {
                $max["index"] = min($max["index"], $maxColLen);
            }
            $indexlen=$max["index"];

            //
            // OUTPUT FIRST ROW
            //
            $rowsep="";
            $rowhead="";
            if ($fShowIndex) {
                $rowsep.="+";
                $rowhead.="|".$pad;
                $rowsep.=str_repeat("-", $indexlen+2*strlen($pad));
                $rowhead.=str_pad("index", $indexlen, " ", STR_PAD_BOTH).$pad;
            }
            foreach ($firstrowkeys as $number=>$keyname) {
                $rowsep.="+";
                $rowhead.="|".$pad;

                $len=$max[$number];
                $rowsep.=str_repeat("-", $len+2*strlen($pad));
                $rowhead.=self::mb_str_pad(mb_substr($keyname, 0, $len, "UTF-8"), $len, " ", STR_PAD_BOTH, "UTF-8").$pad;
            }
            $rowsep.="+\n";
            $rowhead.="|\n";

            //
            // OUTPUT LINES
            //
            $ret.=$rowsep.$rowhead.$rowsep;
            foreach ($array as $indexname=>$line) {
                if ($fShowIndex) {
                    $ret.="|".$pad.self::mb_str_pad(mb_substr($indexname, 0, $len, "UTF-8"), $indexlen, " ", STR_PAD_LEFT, "UTF-8").$pad;
                }
                foreach ($firstrowkeys as $number=>$keyname) {
                    $len=$max[$number];
                    $ret.="|".$pad.self::mb_str_pad(mb_substr(str_replace(array("\r","\n","\0"), array("\\r", "\\n", "\\0"), $line[$keyname]), 0, $len, "UTF-8"), $len, " ", STR_PAD_LEFT).$pad;
                }
                $ret.="|\n";
            }
            $ret.=$rowsep;
        } elseif ($format=="html") {
            reset($array);
            $firstrowkeys=array_keys(current($array));

            //
            // OUTPUT FIRST ROW
            //
            $rowhead = "<thead><tr>";
            if ($fShowIndex) {
                $rowhead .= "<th>index</th>";
            }
            foreach ($firstrowkeys as $number=>$keyname) {
                $rowhead .= "<th>$keyname</th>";
            }
            $rowhead .= "</tr></thead>";

            //
            // OUTPUT LINES
            //
            $tbody = "";
            foreach ($array as $indexname=>$line) {
                $tbody .= "<tr>";
                if ($fShowIndex) {
                    $tbody .= "<td>$indexname</td>\n";
                }
                foreach ($firstrowkeys as $number=>$keyname) {
                    $val = htmlspecialchars($line[$keyname]);
                    $tbody .= "<td>$val</td>";;
                }
                $tbody .= "</tr>\n";
            }

            $ret = "<table>\n$rowhead\n<tbody>\n$tbody</tbody>\n</table>\n";
        }

        return $ret;
    }

    /**
     * Prepend or Append a string on each line of a string
     * @param string $text
     * @param string[optional] $prepend
     * @param string[optional] $append
     */
    public static function appendPrepend($text, $prepend="", $append="")
    {
        $aText=explode("\n", $text);
        // remove last line if it is empty (ie the last line ended with \n)
        $last=array_pop($aText);
        if (strlen($last)) { $aText[]=$last; }
        $text3="";
        foreach ($aText as $line) {
            $text3.=$prepend.$line.$append."\n";
        }
        return $text3;
    }


    /**
     * Idem realpath, mais fonctionne aussi sur fichier non existant
     * Inspiré par Sven Arduwie http://fr.php.net/manual/fr/function.realpath.php#84012 , mais retourne chemin absolu
     * @param string $path
     * @return string
     */
    public static function realpath($path)
    {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $absolutes);

    }

    /**
     * Print a hex dump of a string
     * 49 4e 46 4f 00 00 00 10 54 61 68 6f 6d 61 00 62     INFO....Tahoma.b
     * 69 31 33 00 d0 b0 00 00                             i13.....
     * @param string $string
     * @return string
     */
    public static function dumpBin($string)
    {
        $ret=$a=$h="";
        for ($i=0,$l=min(65536,strlen($string))-1;$i<=$l;$i++) {
            $c=ord(substr($string,$i,1));
            $h.=str_pad(dechex($c),2,0,STR_PAD_LEFT)." ";
            $a.=($c<32)?("."):(utf8_encode(chr($c)));
            if (($i==$l)||(($i&15)==15)) {
                $ret.=str_pad(dechex($i&0xfff0),4,0,STR_PAD_LEFT).":  ".str_pad($h,50)."$a\n";
                $a=$h="";
            }
        }
        return $ret;
    }



    /**
     * Function ripped from http://php.net/manual/fr/function.str-pad.php
     * Kari &#34;Haprog&#34; Sderholm 21-Mar-2009 02:43
     * @param string $input
     * @param integer $pad_length
     * @param string  $pad_string
     * @param integer $pad_type
     * @param string  $charset
     * @return string
     */
    public static function mb_str_pad($input, $pad_length, $pad_string=' ', $pad_type=STR_PAD_RIGHT, $charset="UTF-8") {
        $diff = strlen($input) - mb_strlen($input, $charset);
        return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
    }

    /**
     * Friendler (more tolerant) json_decode
     * Handles spaces, not-quoted object index and comments
     * usage same as the official json_decode
     */
    public static function json_decode(/* $json, $assoc=false, $depth=512, $options=0 */)
    {
        $p = func_get_args();
        // from: 1franck
        // Sometime, i need to allow comments in json file. So i wrote a small func to clean comments in a json string before decoding it:
        // (replaced double quotes by single quotes)
        $p[0] = preg_replace('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#', '', $p[0]);

        // from: phpdoc at badassawesome dot com:
        // I added a 3rd regex to the json_decode_nice function by "colin.mollenhour.com" to handle a trailing comma in json definition.
        $p[0] = str_replace(array("\n","\r"),"", $p[0]);
        $p[0] = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":', $p[0]);
        $p[0] = preg_replace('/(,)\s*}$/','}', $p[0]);
        return call_user_func_array('\json_decode', $p);
    }

}
