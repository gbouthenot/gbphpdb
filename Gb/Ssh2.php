<?php

/**
 * Gb_Ssh2
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


Class Gb_Ssh2
{
    /**
     * Renvoie la révision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
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

    protected $_user;
    protected $_host;
    protected $_auth;
    protected $_port;
    protected $_methods;
    protected $_codepage;
    
    /**
     * Constructeur
     * @param string $user
     * @param null|string|array $auth null, password or array(pubkeyfile, prvkeyfile[, prvkeypass]) 
     * @param string $host
     * @param integer[optional] $port
     * @param string[optinoal] $codepage (default CP850)
     * @param array[optional] $methods
     * @throws Gb_Exception
     */
    public function __construct($user, $auth, $host, $port=null, $codepage=null, $methods=null)
    {
        if (!function_exists("ssh2_connect")) {
            throw new Gb_Exception("extension ssh2 not available");
        }
        if (null === $port) {
            $port = 22;
        }
        if (null === $methods) {
            $methods = array();
        }
        if (null === $codepage) {
            $codepage = "CP850";
        }
        $this->_host    = $host;
        $this->_port    = $port;
        $this->_methods = $methods;
        $this->_user    = $user;
        $this->_auth    = $auth;
        $this->_codepage= $codepage;
    }
    
    protected $_rsc = null;
    
    /**
     * ensure the ssh connection is set, and the server accepted authentication
     * @throws Gb_Exception
     */
    public function connect()
    {
        if (null !== $this->_rsc) {
            return;
        }
        
        $link = ssh2_connect($this->_host, $this->_port, $this->_methods, array(
            "ignore"=>array($this, "_ignore"),
            "debug"=>array($this, "_debug"),
            "macerror"=>array($this, "_macerror"),
            "disconnect"=>array($this, "_disconnect")
        ));
        
        if (false === $link) {
            throw new Gb_Exception("cannot connect to ssh server");
        }
        
        
        $res = null;
        if (is_string($this->_auth)) {
            $res = ssh2_auth_password($link, $this->_user, $this->_auth);
        } elseif (is_array($this->_auth) && count($this->_auth)>=2 ) {
            if (3 === count($this->_auth)) {
                // use pubkeyfile, prvkeyfile, prvkeypass
                print_r($this->_auth);
                $res = ssh2_auth_pubkey_file($link, $this->_user, $this->_auth[0], $this->_auth[1], $this->_auth[2]);
            } else {
                // use pubkeyfile, prvkeyfile
                $res = ssh2_auth_pubkey_file($link, $this->_user, $this->_auth[0], $this->_auth[1]);
            }
        } else {
            $res = ssh2_auth_none($link, $this->_user);
        }
        
        if (true === $res) {
            $this->_rsc = $link;
        } else {
            if (false === $res) {
                $msg = "the ssh server refused authentication";
            } else {
                $msg = "the ssh server refused none authentication; accepted authentication methods: ".serialize($res);
            }
            echo $msg;
            throw new Gb_Exception($msg);
        }
    }
    
    
    // callbacks
    protected function _ignore($message) { echo "IGN ".$message.PHP_EOL; }
    protected function _debug($message, $language, $aways_display) { echo "DBG ".$message.PHP_EOL;}
    protected function _macerror($packet) { }
    protected function _disconnect($reason, $message, $language) { echo "DIS ".$reason." ".$message.PHP_EOL;}
    
    
    
    public function exec($string)
    {
        //$string64 = base64_encode( mb_convert_encoding($string, $this->_codepage, "UTF-8"));
        $string64 = base64_encode($string);
        $res = $this->execRaw("set -f +B && `echo $string64 | base64 -d`");
//        $res = $this->execRaw("set -f +B && echo $string64 | base64 -d");
//        Gb_Log::logNotice($string64, strlen($string64));
        
        $res->exec = $string;
        return $res;
    }

    public function execRaw($cmd)
    {
        $this->connect();
        
        $stdio = ssh2_exec($this->_rsc, $cmd);

        stream_set_blocking($stdio, true);
        
        $stdout = stream_get_contents($stdio);
        $stderr = stream_get_contents(ssh2_fetch_stream($stdio, SSH2_STREAM_STDERR));
        fclose($stdio);
        
        $out = mb_convert_encoding($stdout, "UTF-8", $this->_codepage);
        $err = mb_convert_encoding($stderr, "UTF-8", $this->_codepage);
        
        echo PHP_EOL;
        
        $ret = new stdClass();
        $ret->stdout     = $stdout;
        $ret->stdoutUtf8 = $out;
        $ret->stderr     = $stderr;
        $ret->stderrUtf8 = $err;
        $ret->execRaw    = $cmd;
        return $ret;
    }
    
}
