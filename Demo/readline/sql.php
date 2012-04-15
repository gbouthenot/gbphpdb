#!/usr/bin/php
<?php

error_reporting(E_ALL);
ini_set("display_errors", true);

require_once __DIR__ . DIRECTORY_SEPARATOR . "..". DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Gb" . DIRECTORY_SEPARATOR . "Util.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "..". DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Gb" . DIRECTORY_SEPARATOR . "Db.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "..". DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Gb" . DIRECTORY_SEPARATOR . "Cache.php";

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


if(0){
    // prevent IDE to warn about undefined function
    function readline_add_history(){}
    function readline(){}
}


// procces command line arguments
if ($argc != 2) {
    echo "usage: ".$argv[0]." favoritename\n";
    exit(1);
}
$favoriteName = strtoupper($argv[1]);


// search the favorites file

// 1 : search in the current directory
$fname = getcwd() . DIRECTORY_SEPARATOR . "favorites.ini";
if (!is_file($fname) || !is_readable($fname)) {
    // 2 : then the directory of the php script
    $fname = dirname(__FILE__) . DIRECTORY_SEPARATOR . "favorites.ini";
    if (!is_file($fname) || !is_readable($fname)) {
        // 3 : then in the user home directory
        $fname = getenv('HOME') . DIRECTORY_SEPARATOR . ".sqlfavorites";
        if (!is_file($fname) || !is_readable($fname)) {
            // 4 : then in the system /etc
            $fname = DIRECTORY_SEPARATOR . "etc" . DIRECTORY_SEPARATOR . "sqlfavorites";
            if (!is_file($fname) || !is_readable($fname)) {
                echo <<<EOF
error: unable to read favorites file. search order is :
 - favorites.ini (in cwd)
 - favorites.ini (in script path)
 - ~/.sqlfavorites
 - /etc/sqlfavorites

EOF;
                exit(2);
            }
        }
    }
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
$cacheId = $dbParams["type"].$dbParams["host"].@$dbParams["port"].@$dbParams["name"];
$cache = new Gb_Cache($cacheId, 0);
$db->setCache($cache);


init_readline();
$aOptions["pager"]="/usr/bin/less -niSF";
$aOptions["pager"]="mcview";
$aOptions["format"]="text";
$aOptions["nopager"]=false;
$aOptions["tmpfile"]=tempnam("", "sql");
$aOptions["maxwidth"]=30;
process_main();
exit(0);





function init_readline()
{
    $history=array(
    "clearcache",
    "search <string|int>",
    "searchcol columnname",
    "maxwidth 0",
    "format <text|csv>",
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
        } elseif ($linelow=="format text") {
            $aOptions["format"] = "text";
        } elseif ($linelow=="format csv") {
            $aOptions["format"] = "csv";
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
        passthru($cmd);
        
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
        } elseif ($upper=="CLEARCACHE") {
            $db->getCache()->clear();
            $ret .= "Cache cleared\n";
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
            $nbTables = count($aTables);
            $runTable = 0;
            $curTime  = time();
            foreach ($aTables as $table) {
                $tableFullName = $table["FULL_NAME"];

                // progress report every 2 seconds
                if (time() >= ($curTime + 2)) {
                    echo "searching $runTable / $nbTables current: $tableFullName\n";
                    $curTime = time();
                }

                $aTableDesc    = $db->getTableDesc($tableFullName);
                $aCols         = $aTableDesc["columns"];
                $fNeedleInt    = is_numeric($needle);
                
                $aWhere=null;
                foreach ($aCols as $aCol) {
                    if ($fNeedleInt && strpos(strtolower($aCol["TYPE"]), "int") !== false) {
                        $aWhere[] = $db->quoteIdentifier($aCol["COLUMN_NAME"])." = ".((int)$needle);
                    } elseif (!$fNeedleInt && strpos(strtolower($aCol["TYPE"]), "char") !== false) {
                        $aWhere[] = $db->quoteIdentifier($aCol["COLUMN_NAME"])." LIKE '%$needle%'";
                    }
                }
                if (count($aWhere)) {
                    $sql = "SELECT * FROM $tableFullName WHERE " . join(" OR ", $aWhere);
                    $data = $db->retrieve_all($sql);
                    if (count($data)) {
                        $ret .= $sql."\n";
                        $ret .= text_format($data)."\n";
                    }
                }
                $runTable++;
            } // foreach $aTables
        } elseif (substr($upper, 0, 10)=="SEARCHCOL ") {
            $needle = substr($text, 10);
            $aTables = $db->getTables();
            $nbTables = count($aTables);
            $runTable = 0;
            $curTime  = time();

            foreach ($aTables as $table) {
                // progress report every 2 seconds
                if (time() >= ($curTime + 2)) {
                    echo "searching $runTable / $nbTables current: $tableFullName\n";
                    $curTime = time();
                }

                $tableFullName = $table["FULL_NAME"];
                $aTableDesc    = $db->getTableDesc($tableFullName);
                $aCols         = $aTableDesc["columns"];
                $aFks          = $aTableDesc["fks"];
                foreach ($aCols as $aCol) {
                    if (stripos($aCol["COLUMN_NAME"], $needle) !== false) {
                        $ret .= $tableFullName . "." . $aCol["COLUMN_NAME"]."\n";
                    }
                }
                foreach ($aFks as $aCol) {
                    if (stripos($aCol["FULL_NAME"]."*".$aCol["COLUMN_NAME"], $needle) !== false) {
                        $ret .= $tableFullName . "." . $aCol["COLUMN_NAME"] . " (foreign key: ". $aCol["FULL_NAME"] .")\n";
                    }
                }
                $runTable++;
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

    switch (strtoupper($aOptions["format"])) {
        case "TEXT":
            $ret .= Gb_String::formatTable($data, "text", $aOptions["maxwidth"], "");
            $ret .= count($data)." lines returned.";
            break;

        case "CSV":
            $ret .= Gb_String::arrayToCsv($data);
            break;
    }

    return $ret;
}



