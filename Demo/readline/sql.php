#!/usr/bin/php
<?php

error_reporting(E_ALL);
ini_set("display_errors", true);

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
$aOptions["maxwidth"]=30;
process_main();
exit(0);





function init_readline()
{
    $history=array(
    "show databases",
    "show tables",
    "maxwidth 0",
    );
    
    foreach ($history as $line) {
        readline_add_history($line);
    }
}

function process_main()
{
    global $aOptions;
    
    do {
        $line    = myreadline();
        $linelow = strtolower($line);        
        if ( strlen(trim($line)) == 0 ) {
            continue;
        }


        if (substr($linelow, 0, 4)=="exit" || substr($linelow, 0, 4)=="quit") {
            return;
        }
        
        readline_add_history($line);
        
        if ($linelow=="nopager") {
            $aOptions["nopager"]=true;
        } elseif ($linelow=="pager") {
            $aOptions["nopager"]=false;
        } elseif (substr($linelow, 0, 9)=="maxwidth ") {
            $maxwidth = substr($line, 9);
            if (is_numeric($maxwidth)) {
                $aOptions["maxwidth"] = (int) $maxwidth;
                echo "maxwidth set to $maxwidth\n";
            } 
        } elseif (substr($linelow, 0, 8)=="maxwidth") {
                echo "maxwidth = ".$aOptions["maxwidth"]."\n";
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
        } elseif (substr($upper, 0, 7)=="SEARCH ") {
            $needle = substr($text, 7);
            $aTables = $db->getTables();
            foreach ($aTables as $table) {
                $tableFullName = $table["FULL_NAME"];
                $aTableDesc    = $db->getTableDesc($tableFullName);
                $aCols         = $aTableDesc["columns"];
                $fNeedleInt    = is_numeric($needle);
                
                $aWhere=null;
                foreach ($aCols as $aCol) {
                    if ($fNeedleInt && strpos(strtolower($aCol["TYPE"]), "int") !== false) {
                        $aWhere[] = $aCol["COLUMN_NAME"]." = ".((int)$needle);
                    } elseif (!$fNeedleInt && strpos(strtolower($aCol["TYPE"]), "char") !== false) {
                        $aWhere[] = $aCol["COLUMN_NAME"]." LIKE '%$needle%'";
                    }
                }
                if (count($aWhere)) {
                    $sql = "SELECT * FROM $tableFullName WHERE " . join(" OR ", $aWhere);
                    $data = $db->retrieve_all($sql);
                    $ret .= $sql."\n";
                    if (count($data)) {
                        $ret .= text_format($data)."\n";
                    }
                }
            } // foreach $aTables
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
    global $aOptions;
    
    $ret = "";
    $ret .= Gb_String::formatTable($data, "text", $aOptions["maxwidth"], "");
    $ret .= count($data)." lines returned.";
    return $ret;
}


