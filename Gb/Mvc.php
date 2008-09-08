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
    
    protected $_pathApplication="../application/";
    protected $_pathControllers="controllers/";
    protected $_pathViews=      "views/";
    protected $_pathHelpers=    "helpers/";
    
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
        if (count($args)==1 && $args[0]=="") {
            $args=array();
        }

        //echo "script: $script<br />req0: $req0<br />req: $req<br />match: ".print_r($match,true)."<br />href: $href<br />req2: $req2<br />args: ".print_r($args,true)."<br />rooturl: $rooturl<br />";
        //exit(0);

        $this->_args=new Gb_Args($args);
        $this->_href=$href;
        $this->_rootUrl=$rooturl;
    }
    
    protected function _initPaths()
    {
        $this->_pathApplication=str_replace("/", DIRECTORY_SEPARATOR, $this->_pathApplication);
        $this->_pathControllers=str_replace("/", DIRECTORY_SEPARATOR, $this->_pathControllers);
        $this->_pathViews=str_replace("/", DIRECTORY_SEPARATOR, $this->_pathViews);
        $this->_pathHelpers=str_replace("/", DIRECTORY_SEPARATOR, $this->_pathHelpers);
    }
    
    
    protected function _initHelpers()
    {
        $path=$this->_pathHelpers;
        $dir=dir($path);
        while (false !== ($f = $dir->read())) {
            $file=$path.$f;
          if (is_file($file) && is_readable($file)) {
              include($file);
          }
        }
        $dir->close();
    }
    
    private function __construct()
    {
        $this->_initPaths();
        $this->_getMvcArgs();
    }
    
    public function start()
    {
        ob_start();
        $cwd=getcwd();
        chdir($this->_pathApplication);
        
        $this->_initHelpers();
        
        $controller=$this->_args->remove();
        echo $this->callController($controller, $this->_args);
        
        chdir($cwd);
    }
    
    public function callController($mvcController, Gb_Args $mvcArgs=null)
    {
        if ($mvcArgs===null) {
            $mvcArgs=new Gb_Args(array());
        }
        
        $oldMvcArgs=$this->_args;
        $this->_args=$mvcArgs;
        
        //        $mvcArgs=$this->_args;
        $mvcHref=$this->_href;
        $mvcRootUrl=$this->_rootUrl;
        $mvcHref;
        $mvcRootUrl;

        $output="";
        $file=$this->_pathControllers."$mvcController/$mvcController.php";
        if (is_file($file) && is_readable($file)) {
            ob_start();
            include($file);
            $output=ob_get_contents();
            ob_end_clean();
        }
        
        $this->_args=$oldMvcArgs;
        return $output;
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
    
    /**
     * Renvoie une URL
     * @param mixed[optional] $args Gb_Args, string ou array
     * 
     * @return string
     */
    public function getUrl($args=null)
    {
        if ($args===null) {
            $args=new Gb_Args(array());
        } elseif (is_string($args)) {
            $args=new Gb_Args($args);
        } elseif (is_array($args)) {
            $args=new Gb_Args($args);
        }
        
        $root=$this->_rootUrl;
        $sArgs=implode("/", $args->getAll());

        return $root.$sArgs."/";
    }
    
    
}
