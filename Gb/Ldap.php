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
    protected $connexion;
    protected $params;

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


    public function __construct($server, $dn = null, $pass = null, $port = null)
    {
        if ($port===null) {
            $port=389;
        }
        $this->params = array(
            "server" => $server,
            "dn" => $dn,
            "pass" => $pass,
            "port" => $port
        );
        $this->connexion = null;
    }




    /**
     * @return false|array false if error, array() if no result
     */
    public function search($basedn, $filter = null, $attrs = null, $sizelimit = null)
    {
        $this->connect();

        if ($attrs===null) {
            $attrs=array();
        }
        $sr=@ldap_search($this->connexion, $basedn, $filter, $attrs, false, $sizelimit, 10);

        if ($sr === false) {
            return false;
        }

        $users=array();

        for ($entry = ldap_first_entry($this->connexion, $sr);
            $entry != false;
            $entry = ldap_next_entry($this->connexion, $entry)) {
            $user = array();//print_r($entry);echo "  -  ".memory_get_peak_usage()."\n";
            $attributes = ldap_get_attributes($this->connexion, $entry);

            for ($i=$attributes['count']; $i-- >0;) {
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
            return self::getFirst1($array[$attribute]);
        }
    }

    protected static function getFirst1($value)
    {
        if (is_array($value)) {
            return self::getFirst1($value[0]);
        } else {
            return $value;
        }
    }


    public function __destruct()
    {
        if ($this->connexion !== null) {
            ldap_close($this->connexion);
        }
    }

    /**
     * Connexion au serveur
     * @throws Exception
     */
    protected function connect()
    {
        if ($this->connexion === null) {
            $server = $this->params["server"];
            $port = $this->params["port"];

            $connexion= @ldap_connect($server, $port);
            if ($connexion === false) {
                throw new Gb_Exception("Cannot connect to ldap server");
            }

            $this->connexion = $connexion;
            $this->bind();
        }
    }


    protected function bind()
    {
        $dn = $this->params["dn"];
        $pass = $this->params["pass"];
        if ($dn !== null) {
            $res = @ldap_bind($this->connexion, $dn, $pass);
        } else {
            $res = @ldap_bind($this->connexion);
        }
        if ($res !== true) {
            throw new Gb_Exception("Cannot bind to ldap server");
        }
    }
}
