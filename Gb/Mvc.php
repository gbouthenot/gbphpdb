<?
/**
 * Gb_Mvc
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Args.php");



Class Gb_Mvc
{
    /**
     * @var Gb_Mvc
     */
    private static $_instance;

    /**
     * @var Gb_Args
     */
    protected $_args;
    
    protected $_href;
    protected $_rootUrl;
    
    /**
     * @return Gb_Mvc
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            $c=__CLASS__;
            self::$_instance=new $c;
        }
        return self::$_instance;
    }
    
    
    protected function _getMvcArgs()
    {
        $match=array();
        
        $script=$_SERVER["SCRIPT_NAME"];           //      /maps/index.php 
        $req0=$_SERVER["REQUEST_URI"];              //      /maps/view/abc/def/ghi/klm
        
        // sanityse $req and remove GET params
        preg_match("@^([a-z/0-9_-]*)@i", $req0, $match);
        $req=$match[1];
        
        // remove the last part of $script
        preg_match("@^(.*/).*/.*$@", $script, $match);
        $rooturl=$match[1];                       //       /maps/
        
        $href="";
        $href.=$_SERVER["SERVER_PORT"]==443 ? "https":"http";
        $href.="://";
        $href.=$_SERVER["SERVER_NAME"];
        $href.=$_SERVER["SERVER_PORT"]!=80 ? ":".$_SERVER["SERVER_PORT"] : "";
        $href.=$rooturl;
        
        $pos=strpos($req, $rooturl);
        if ($pos!==false) {
            $req=substr($req, $pos+strlen($rooturl));
        }
        
        $req2=trim($req, "/");
        $args=explode("/", $req2);
        if (count($args)==1 && $args[0]="") {
            $args=array();
        }

        //echo "script: $script<br />req0: $req0<br />req: $req<br />match: ".print_r($match,true)."<br />href: $href<br />req2: $req2<br />args: ".print_r($args,true)."<br />rooturl: $rooturl<br />";
        //exit(0);

        $this->_args=new Gb_Args($args);
        $this->_href=$href;
        $this->_rootUrl=$rooturl;
    }
    
    private function __construct()
    {
        $this->_getMvcArgs();
    }
    
    public function start()
    {
        $cwd=getcwd();
        chdir("../application");
        
        $controller=$this->_args->remove();
        $this->callController($controller);
        
        chdir($cwd);
    }
    
    public function callController($mvcController)
    {
        $mvcArgs=$this->_args;
        $mvcHref=$this->_href;
        $mvcRootUrl=$this->_rootUrl;
        $mvcArgs;
        $mvcHref;
        $mvcRootUrl;
        $mvcController;

        $file="controllers/$mvcController/$mvcController.php";
        if (is_file($file) && is_readable($file)) {
            include($file);
        }
    }

    public function getHref()
    {
        return $this->_href;
    }

    public function getRootUrl()
    {
        return $this->_rootUrl;
    }
    
    /**
     * @return Gb_Args
     */
    public function getArgs()
    {
        return $this->_args;
    }
}
