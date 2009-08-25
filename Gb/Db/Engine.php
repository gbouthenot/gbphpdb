<?php
/**
 * Gb_Db_Engine
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Db.php");

Class Gb_Db_Engine
{
    protected $_aliases;

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que pr�cis�e, ou Gb_Exception
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
     * constructeur
     *
     * @param string[optional] $sessionprefix
     * @param array[optional] $aOptions options pass�es � Gb_Form2
     */
    public function __construct()
    {
        $this->_aliases=array();
    }
    /**
     * @return array
     */
    public function getAliases()
    {
        return $this->_aliases;
    }
    /**
     * @param array $aliases array("name"=>Gb_Db, ...)
     * @return Gb_Db_Engine provides a fluent interface
     */
    public function setAliases(array $aliases)
    {
        $this->_aliases=$aliases;
        return $this;
    }
    /**
     * @param string $name
     * @param Gb_Db $db
     * @return Gb_Db_Engine provides a fluent interface
     */
    public function setAlias($name, Gb_Db $db)
    {
        $this->_aliases[$name]=$db;
        return $this;
    }

    
    private $_tmpParams;
    /**
     * Returns parameters asked by the request
     *
     * @param array $request
     * @return array array("name1", ...)
     */
    public function findParams(array $request)
    {
        $this->_tmpParams=array();
        array_walk_recursive($request, array(__CLASS__, "_paramWalker"));
        return $this->_tmpParams;
    }
    
    /**
     * Execute
     * @param array $request
     * @param array[optional] $bindParams
     */
    public function execute(array $request, array $bindParams=array())
    {
        return $this->_exec_array($request, $bindParams);
    }

    
    
    
    /**
     * Execute array
     * @param array $array
     * @param array $bindParams
     * @return array
     * @throws Gb_Exception
     */
    protected function _exec_array($array, array $params)
    {
        $type=strtoupper($array[0]);
        if ($type=="SELECT") {
            $data=$this->_exec_select($array[1], $array[2], $params);
        } elseif ($type=="UNION") {
            $data=$this->_exec_union($array, $params);
        } elseif ($type=="JOIN") {
            $data=$this->_exec_join($array, "JOIN", $params);
        } elseif ($type=="LEFT JOIN") {
            $data=$this->_exec_join($array, "LEFT JOIN", $params);
        } elseif ($type=="MEMJOIN") {
            $data=$this->_exec_memjoin($array, "MEMJOIN", $params);
        } elseif ($type=="LEFT MEMJOIN") {
            $data=$this->_exec_memjoin($array, "LEFT MEMJOIN", $params);
        } else {
            throw new Gb_Exception("Unknown request type '$type'");
        }
        
        return $data;
    }
    
    
    protected function _exec_union($array, array $params)
    {
        $data=array();
        $first=true;
        foreach ($array as $key=>$subarray) {
            if ($first) { // ignore la premi�re valeur
                $first=false;
            } else {
                $this->_aTmpData[$key]=$this->_exec_array($subarray, $params);
                $data=array_merge($data, $this->_aTmpData[$key]);
            }
        }
        return $data;    
    }

    /**
     * Ex�cute la requete Gauche, et pour chaque ligne, ex�cute la requete droite, en remplacant les parametres
     * Si la requete droite renvoit plusieurs lignes, la fonction renvoit autant de lignes.
     * Si la requete droite renvoit 0 ligne, et qu'on effectue une requete "LEFT JOIN", inclus la ligne gauche seule.
     *
     * @param array $array
     * @param string $type
     * @return array
     */
    protected function _exec_join(array $array, $type, array $params)
    {
        if (count($array)!=3 || !is_array($array[1]) || !is_array($array[2]) || $array[2][0]!="SELECT") {
            throw new Exception("Join syntax must be array('[LEFT ]JOIN', array(...), array('SELECT', 'host', 'sql'))");
        }
        $left=$this->_exec_array($array[1], $params);
        
        $righthost=$array[2][1];
        $rightsql=$array[2][2];
        $data=array();
        $out=null;

        preg_match_all("/##(.*)##/U", $rightsql, $out);
         // renvoie $out=array(array(                            ), array(                      )) si 0 match
         // renvoie $out=array(array("##USA,usa##", ##USA2,usa2##), array("USA,usa", "USA2,usa2")) si 2 matches
        //echo "<pre>";print_r($out);echo "</pre>";

        foreach ($left as $left_line) {
            $sql=$rightsql;
            if (is_array($out) && is_array($out[0]) && is_array($out[1])) {
                foreach ($out[1] as $replace) { // USA,usa   , USA2,usa2
                    if (strlen($replace)) {
                        $by=$left_line[$replace];
                        $by=$this->_getdb($righthost)->quote($by);
                        $sql=str_replace("##".$replace."##", $by, $sql);
                    }
                }
            }
            $right=$this->_exec_select($righthost, $sql, $params);
            if ($type=="LEFT JOIN" && count($right)==0) {
                $data[]=$left_line;
            } else {
                foreach ($right as $right_line) {
                    $data[]=array_merge($left_line, $right_line);
                }
            }
        }

        return $data;
    }

    /**
     * Ex�cute la requete Gauche, puis la requ�te droite et pour chaque ligne de la requete gauche, recherche dans la requ�te droite
     * Si la requete droite renvoit plusieurs lignes, la fonction renvoit autant de lignes.
     * Si la requete droite renvoit 0 ligne, et qu'on effectue une requete "LEFT JOIN", inclus la ligne gauche seule.
     *
     * @param array $array
     * @param string $type
     * @return array
     */
    protected function _exec_memjoin(array $array, $type, array $params)
    {
        if ( count($array)!=4 || !is_array($array[1]) || !is_array($array[2]) || !is_array($array[3]) ) {
            throw new Exception("MemJoin syntax must be array('[LEFT ]MEMJOIN', array(...), array(...), array(leftcol=>rightcol))");
        }
        $left=$this->_exec_array($array[1], $params);
        $right=$this->_exec_array($array[2], $params);
        $match=$array[3];   

        $data=array();

        foreach ($left as $left_line) {
            $found=array();
            foreach ($right as $right_line) {
                foreach ($match as $leftcol=>$rightcol) {
                    if ( $left_line[$leftcol] != $right_line[$rightcol] ) {
                        break;
                    }
                    $found[]=$right_line;
                }
            }
            if ($type=="LEFT MEMJOIN" && count($found)==0) {
                $data[]=$left_line;
            } else {
                foreach ($found as $found_line) {
                    $data[]=array_merge($left_line, $found_line);
                }
            }
            
        }

        return $data;
    }
    
    /**
     * Return select result
     *
     * @param string $host
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function _exec_select($host, $sql, array $params)
    {
        $db=$this->_getdb($host);
        foreach ($params as $key=>$val) {
            $sql=str_replace("**$key**", $db->quote($val), $sql);
        }
        return $db->retrieve_all($sql, array());
    }
    
    /**
     * @param string $host
     * @return Gb_Db
     * @throws Gb_Exception
     */
    protected function _getdb($host)
    {
        if (isset($this->_aliases[$host])) {
            return $this->_aliases[$host];
        } else {
            throw new Gb_Exception("host alias '$host' unknown");
        }
    }

    
    private function _paramWalker($item, $key)
    {   if ($key==2 && is_string($item)) {
            $out=null;
            preg_match_all('/\*\*(.*)\*\*/U', $item, $out);
         // renvoie $out=array(array(                            ), array(                      )) si 0 match
         // renvoie $out=array(array("**USA,usa**", **USA2,usa2**), array("USA,usa", "USA2,usa2")) si 2 matches
        //echo "<pre>";print_r($out);echo "</pre>";
            foreach ($out[1] as $param){
                $this->_tmpParams[]=$param;
            }
        }
    }
    
}
