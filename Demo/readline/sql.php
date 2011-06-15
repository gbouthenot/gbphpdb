#!/usr/bin/php
<?php

set_include_path("/home/gbouthen/web/neon/include/".PATH_SEPARATOR.get_include_path());
//set_include_path("/home/gbouthen/web/neon/include/");

require_once "Gb/Util.php";
require_once "Gb/Db.php";

$items = array();

load_extension("pdo");
//load_extension("pdo_mysql");
//load_extension("pdo_oci");
load_extension("readline");

function load_extension($name)
{
    if(!extension_loaded($name)) {
        if(!dl("php_{$name}.dll")) {
            trigger_error("$name extension could not be loaded.\n", E_USER_ERROR);
        }
    }
}



// procces command line arguments
if ($argc != 2) {
    echo "usage: ".$argv[0]." favoritename\n";
    exit(1);
}
$favoriteName = strtoupper($argv[1]);

$fname = "favorites.ini";
if (!is_file($fname) || !is_readable($fname)) {
    echo "error: unable to read file $fname\n";
    exit(2);
}

$aFavorites = parse_ini_file($fname, true);
if (!isset($aFavorites[$favoriteName])) {
    echo "error: favorite $favoriteName not found in file $fname\n";
    exit(2);
}

$dbParams = $aFavorites[$favoriteName];
if (!is_array($dbParams)) {
    echo "error: favorite $favoriteName in file $fname is not correctly defined\n";
    exit(2);
}

$db = new Gb_Db($dbParams);



init_readline();
$aOptions["pager"]="/usr/bin/less -niSF";
$aOptions["pager"]="mcview";
$aOptions["nopager"]=false;
$aOptions["tmpfile"]=tempnam("", "sql");
process_main();
exit(0);





function init_readline()
{
    $history=array(
    "show databases",
    "show tables",
    );
    
    foreach ($history as $line) {
        readline_add_history($line);
    }
}

function process_main()
{
    global $aOptions;
    
    do {
        $line=myreadline();        
        if ( strlen(trim($line)) == 0 ) {
            continue;
        }


        if ($line=="exit") {
            return;
        }
        
        readline_add_history($line);
        
        if ($line=="nopager") {
            $aOptions["nopager"]=true;
        } elseif ($line=="pager") {
            $aOptions["nopager"]=false;
        } else {
            $ret=process($line);
    
            pager($ret);
        }
    } while (1);
}



function myreadline()
{
    $line_pre="";
    
    do {
        if (strlen($line_pre)) {
            $line = readline("");
        } else {
            $line = readline("sql> ");
        }

        if (substr($line,-1)=="\\") {
            $line_pre .= substr($line, 0, strlen($line)-1);
            $line="";
        } else {
            $line = $line_pre.$line;
        }
    } while ( strlen($line)==0 );

    return $line;
}



function pager($text)
{
    global $aOptions;

    if ($aOptions["nopager"]) {
        echo $text."\n";
    } else {
        $tmpfname = $aOptions["tmpfile"];
        
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $text);
        fclose($handle);
        
        // traitement
        $cmd=$aOptions["pager"]." $tmpfname";
        $abc=passthru($cmd, &$int);
        
        unlink($tmpfname);
    }
}







function process($text)
{
    global $db, $history, $history_current;
    $history[] = $text;
    $history_current = count($history);
    $ret = "";

    try {
        $upper=strtoupper($text);
        if ( $upper=="SHOW TABLES" || $upper=="DESC" ) {
            $data = $db->getTables();
            $ret .= text_format($data);
        } elseif (substr($upper, 0, 5)=="DESC ") {
            $tableDesc=$db->getTableDesc(substr($text, 5));
            $ret .= text_format($tableDesc["columns"]);
            $ret .= "\nPrimary key:\n";
            $ret .= text_format($tableDesc["pk"]);
            $ret .= "\nForeign keys:\n";
            $ret .= text_format($tableDesc["fks"]);
        } else {
            $data = $db->retrieve_all($text);
            $ret .= text_format($data);
        }
    } catch (Exception $e) {
        return $ret.$e->getMessage();
    }
    return $ret;
}





function text_format($data)
{
    $ret = "";
    if (count($data)) {
        $maxwidth=array();
        foreach ($data as $row){
            foreach ($row as $colname=>$value) {
                if (!isset($maxwidth[$colname])) {
                    $maxwidth[$colname]=0;
                }
                $maxwidth[$colname]=max($maxwidth[$colname], strlen($value));
            }
        }
        
        // En-tête
        $header="|";
        $hr="|";
        foreach ($maxwidth as $colname=>$width) {
                $maxwidth[$colname]=max($maxwidth[$colname], strlen($colname));
                $header.=str_pad($colname, $maxwidth[$colname], " ", STR_PAD_BOTH);
                $hr.=str_repeat("-", $maxwidth[$colname]);
                $header.="|";
                $hr.="|";
        }
        $top=str_repeat("-", strlen($header))."\n";
        $header.="\n";
        $hr.="\n";
        $ret=$top.$header.$hr;
        
        
        foreach ($data as $row){
            $ret.="|";
            foreach ($row as $colname=>$value) {
                $ret.=str_pad($value, $maxwidth[$colname], " ", STR_PAD_LEFT);
                $ret.="|";
            }
            $ret.="\n";
        }
        $ret.=$top;
    }
    
    $ret .= count($data)." lines returned.";
    
    return $ret;
}



