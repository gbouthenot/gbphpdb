<?php
/**
 * Gb_Mafu
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


class Gb_Mafu
{
    protected $_fServerConfigured;
    protected $_apcName;
    protected $_apcPrefix;
    protected $_apcFreq;

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
    
    public function __construct()
    {
        if (function_exists('apc_fetch') && ini_get('apc.enabled') && ini_get('apc.rfc1867')) {
            $this->_fServerConfigured=true;
            $this->_apcName=ini_get('apc.rfc1867_name');
            $this->_apcPrefix=ini_get('apc.rfc1867_prefix');
            $this->_apcFreq=ini_get('apc.rfc1867_freq');
        } else {
            $this->_fServerConfigured=false;
        }
    }

    public function getFiles($path=null)
    {
        $files2=array();

        // ensure the given file has been uploaded
        if (isset($_FILES) && is_array($_FILES)) {
            foreach ($_FILES as $file) {
                // only proceed if no errors have occurred
                if ($file['error'] != UPLOAD_ERR_OK)
                    continue;

                if ($path == null) {
                    $file["fullpath"]=$file["tmp_name"];
                } else {
                    // write the uploaded file to the filesystem
                    $fullpath = $path . DIRECTORY_SEPARATOR . basename($file['name']);
                    if (!move_uploaded_file($file['tmp_name'], $fullpath)) {
                        continue;
                    }
                    $file["fullpath"] = $fullpath;
                }

                $file["path"]=dirname($file["fullpath"]);
                $file["filename"]=basename($file["fullpath"]);
                unset($file["tmp_name"]);
                $files2[]=$file;
            }
        }

        return $files2;
    }

    
    public function getUploadStatus($id=null)
    {
        if ($id===null) {
            $return=false;
            if (isset($_POST['gbfu_getprogress'])) {
                $id = isset($_POST['id']) ? $_POST['id'] : 0;
            } else {
                // not a getprogress request: returns
                return $id;
            }
        } else {
            $return=true;
        }

        $ret=array(
            'id'       => $id,
            'finished' => false,
            'percent'  => 0,
            'total'    => 0,
            'complete' => 0
        );

        // if we can't retrieve the status or the upload has finished just return
        if (!($this->fServerConfigured()) || $ret['finished']) {
            return $ret;
        }

        if (0){function apc_fetch(){}}
        // retrieve the upload data from APC
        $status = apc_fetch($this->apcPrefix() . $id);
        if ( !is_array($status) ) {
            $status=array("done"=>1, "percent"=>100);
        } elseif ( !isset($status["done"]) || $status["done"]!=0) {
            $status["done"]=1;
            $status["percent"]=100;
        } else {
            $status["percent"]=100;
            if ($status["total"]>0) {
                $status["percent"]=round($status["current"]/$status["total"]*100);
            }
        }
        $status["id"]=$id;
        if (!isset($status["name"])) {
            $status["name"]="";
        }

        if (!$return) {
            header('Content-type: application/json');
            echo json_encode($status);
            exit(0);
        }
        return $status;
    }




    // ACCESSORS:
    public function fServerConfigured()
    {
        return $this->_fServerConfigured;
    }
    public function apcName()
    {
        return $this->_apcName;
    }
    public function apcPrefix()
    {
        return $this->_apcPrefix;
    }
    public function apcFreq()
    {
        return $this->_apcFreq;
    }
}
