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
}

require_once(_GB_PATH."Exception.php");


class Gb_String
{
  // " et $ ignorés
  const STR_SRC=  "' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~€?‚ƒ„…†‡ˆ‰Š‹Œ???‘’“”•–—˜™š›œ?Ÿ ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏĞÑÒÓÔÕÖ×ØÙÚÛÜİŞßàáâãäåæçèéêëìíîïğñòóôõö÷øùúûüış";
  const STR_UPPER="' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`ABCDEFGHIJKLMNOPQRSTUVWXYZ{|}~€?,F,_†‡ˆ%S‹O?Z??''“”.--˜TS›O?ZY IC£¤¥|§¨C2<¬­R¯°±23'UQ.¸10>¼½¾?AAAAAAACEEEEIIIIDNOOOOOXOUUUUYşBAAAAAAACEEEEIIIIONOOOOO/OUUUUYş";

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
        if ( strlen($sTime)==23 )
            $sTime=substr($sTime, 0, 19);
        if ( strlen($sTime)>=26 )
            $sTime=substr($sTime, 0, 19);
        if ( strlen($sTime)!=19 )
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
            list($y,$m,$d)=split("-",$d);
            $d=substr($d,0,2).'/'.$m.'/'.$y.substr($d,2);
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
     * comme explode mais renvoie array() au lieu de array("") si l'élément n'a pas été trouvé
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
     * Transforme un array en format CSV
     *
     * @param array $data données au même format que 
     * @return string la chaine en csv
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
            $ret.=$ind.";";
        }
        $ret.="\n";
        
        foreach($data as $ligne) {
            foreach(array_keys($firstligne) as $ind) {
                $ret.=$ligne[$ind].";";
            }
            $ret.="\n";
        }
        
        return $ret;
    }
    
}
