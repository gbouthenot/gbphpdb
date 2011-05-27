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
            $split = split("-",$d);
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
            list($d,$m,$y)=split('/',$d);
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
     * Transform an array to CSV format. Replace newlines by " - ".
     *
     * @param array $data array(array("field"=>$value, ...), ...) 
     * @return string
     */
    public static function arrayToCsv(array $data)
    {
        if (count($data)==0) {
            return "";
        }
        $ret="";
        
        // 1ère ligne du csv: les entêtes
        $firstligne=$data[0];
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
        
        return $ret;
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
     * @param string $format text|html
     * @return string
     */
    public static function formatTable(array $array, $format)
    {
        $format=strtolower($format);
        $ret="";
        if (count($array)==0) {
            return "";        
        }
        
        // should we also display the key index ?
        $fShowIndex=false;
        $firstkeys=array_keys($array);
        $firstkey=$firstkeys[0];
        if ($firstkey !== 0) {
            $fShowIndex=true;
        }

        if ($format=="text") {
            reset($array);
            $firstrowkeys=array_keys(current($array));
                        
            // get the max length of each column
            $max=array();
            // first the column name
            $max["index"]=strlen("index");
            foreach ($firstrowkeys as $number=>$keyname) {
                $max[$number]=strlen($keyname);
            }
            
            // then the column values
            foreach ($array as $indexname=>$line) {
                $max["index"]=max($max["index"], strlen($indexname));
                foreach ($firstrowkeys as $number=>$keyname) {
                    $max[$number]=max($max[$number], strlen($line[$keyname]));
                }
            }
            
            $indexlen=$max["index"];
            $rowsep="";
            $rowhead="";
            if ($fShowIndex) {
                $rowsep.="+";
                $rowhead.="| ";
                $rowsep.=str_repeat("-", $indexlen+2);
                $rowhead.=str_pad("index", $indexlen, " ", STR_PAD_BOTH)." ";          
            }
            foreach ($firstrowkeys as $number=>$keyname) {
                $rowsep.="+";
                $rowhead.="| ";

                $len=$max[$number];
                $rowsep.=str_repeat("-", $len+2);
                $rowhead.=str_pad($keyname, $len, " ", STR_PAD_BOTH)." ";          
            }
            $rowsep.="+\n";
            $rowhead.="|\n";
            
            $ret.=$rowsep.$rowhead.$rowsep;
            foreach ($array as $indexname=>$line) {
                if ($fShowIndex) {
                    $ret.="| ".str_pad($indexname, $indexlen, " ", STR_PAD_LEFT)." ";
                }
                foreach ($firstrowkeys as $number=>$keyname) {
                    $len=$max[$number];
                    $ret.="| ".str_pad($line[$keyname], $len, " ", STR_PAD_LEFT)." ";
                }
                $ret.="|\n";
            }
            $ret.=$rowsep;
        } elseif ($format=="html") {
            
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
        $aText=split("\n", $text);
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
}
