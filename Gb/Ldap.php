<?php
/**
 * Gb_Ldap
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


class Gb_Ldap
{
    protected static $_connexion;
    
    protected static $_params;
    
    protected static $_shutdownRegistred;

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que pr�cis�e, ou Gb_Exception
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
    
    
    function __construct($server, $dn=null, $pass=null, $port=null)
    {
        $par=self::$_params;
        if ( self::$_connexion === null || $server!=$par[0] || $dn!=$par[1] ) {
            // serveur non connecté ou paramètres différents
            $this->_shutdown();
            $this->_connect($server, $port);
        }
        if ( $pass!=$par[2] || $port!=$par[3] ) {echo "z";
            $this->_bind($dn, $pass);
        }
        
        self::$_params=array($server, $dn, $pass, $port);
        
        if (self::$_shutdownRegistred !== true) {
            register_shutdown_function(array($this, "_shutdown"));
            self::$_shutdownRegistred = true;
        }
    }

    
    
    
    public function searchtest()
    {
        
        $ds=self::$_connexion;
        echo "Searching for (sn=S*) ...\n";
        // Search surname entry
        $sr=ldap_search($ds, "dc=univ-fcomte,dc=fr", "uid=gbouthen");  
        echo "Search result is " . $sr . "<br />";
    
        echo "Number of entires returned is " . ldap_count_entries($ds, $sr) . "<br />";
    
        echo "Getting entries ...<p>";
        $info = ldap_get_entries($ds, $sr);
        echo "Data for " . $info["count"] . " items returned:<p>";
    
        for ($i=0; $i<$info["count"]; $i++) {
            echo "dn is: " . $info[$i]["dn"] . "<br />";
            echo "first cn entry is: " . $info[$i]["cn"][0] . "<br />";
            echo "first email entry is: " . $info[$i]["mail"][0] . "<br /><hr />";
        }
    }    
    
    
    
    
    
    
    public function search($basedn, $filter=null, $attrs=null, $sizelimit=null)
    {
        if ($attrs===null) {
            $attrs=array();
        }
        $sr=@ldap_search(self::$_connexion, $basedn, $filter, $attrs, false, $sizelimit, 10);
        
        if ($sr === false) {
            return false;
        }
        

        $users=array();
        
        for ($entry = ldap_first_entry(self::$_connexion, $sr); $entry!=false; $entry = ldap_next_entry(self::$_connexion, $entry)) {
            $user = array();//print_r($entry);echo "  -  ".memory_get_peak_usage()."\n";
            $attributes = ldap_get_attributes(self::$_connexion, $entry);
            
            for($i=$attributes['count']; $i-- >0; ) {
                if ($attributes[$attributes[$i]]["count"] == 1) {
                    $user[$attributes[$i]] = $attributes[$attributes[$i]][0];
                } else {
                    unset( $attributes[$attributes[$i]]["count"] );
                    $user[$attributes[$i]] = $attributes[$attributes[$i]];
                }
            }
            $users[] = $user;
        }
        
        ldap_free_result($sr);
        
        return $users;
    }
    
    
    // récupère le premier élément qui n'est pas un array
    // si l'attribut n'est pas définit dans $array, renvoie null
    public static function getFirst(array $array, $attribute)
    {
        if (!isset($array[$attribute])) {
            return null;
        } else {
            return self::_getFirst1($array[$attribute]);
        }
    }

    protected static function _getFirst1($value)
    {
        if (is_array($value)) {
            return self::_getFirst1($value[0]);
        } else {
            return $value;
        }
    }
   
    
    /**
     * Ne fait rien du tout, puisque les paramètres sont statiques, la connexion reste ouverte
     */
    function __destruct()
    {
    }
    
    function _shutdown()
    {
        if (self::$_connexion !== null ) {
            ldap_close(self::$_connexion);
            self::$_connexion = self::$_params = null;
        }
    }
    
   
    /**
     * Connexion au serveur
     * @throws Exception
     */
    protected function _connect($server, $port=null)
    {
        if ($port===null) {
            $port=389;
        }
        
        $connexion= ldap_connect($server, $port);
        if ($connexion === FALSE) {
            throw new Gb_Exception("Cannot connect to $server, port $port, errno=".ldap_errno().", errmsg=".ldap_error());
        }
        
        self::$_connexion = $connexion;
    }
    
    
    protected function _bind($dn=null, $pass=null)
    {
        if ($dn !== null) {
            $res = ldap_bind(self::$_connexion, $dn, $pass);
        } else {
            $res = ldap_bind(self::$_connexion);
        }
        if (!$res) {
            throw new Gb_Exception("Cannot bind, errno=".ldap_errno().", errmsg=".ldap_error());
        }
    }
    
    
}
