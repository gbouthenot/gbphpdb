#!/usr/bin/php
<?php

//require_once("Zend/Db.php");
require_once("Gb/Db.php");
require_once("Gb/Response.php");

//if ( $GLOBALS['argc']==2 && $GLOBALS['argv'][1]=='commit' )
//	echo "OK";

//exit(1);

$oci_host=$mysql_host=$pgsql_host="";

$oci_host="apogee.univ-fcomte.fr";
$oci_port=1524;
$oci_user="gbouthen";
$oci_pass="***REMOVED***";
$oci_name="APOTEST";

$mysql_host="localhost";
$mysql_port=3306;
$mysql_user="root";
$mysql_pass="***REMOVED***";
$mysql_name="mysql";

$pgsql_host="localhost";
$pgsql_port=5432;
$pgsql_user="ticeval";
$pgsql_pass="ticeval";
$pgsql_name="postgres";

$test_failed=$test_success=$test_tried=0;

if (strlen($pgsql_host)) {
    try {
        echo "*** Connexion avec Zend_Db --> Pdo_pgsql\n";
        $test_tried++;
    $conn=Zend_Db::factory("Pdo_Pgsql", array("host"=>$pgsql_host, "port"=>$pgsql_port, "username"=>$pgsql_user, "password"=>$pgsql_pass, "dbname"=>$pgsql_name));
        //$conn->getConnection();
        $stmt=$conn->query("SELECT NOW()");
        $s=$stmt->fetch(Zend_Db::FETCH_ASSOC);
        print_r($s);
        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}



if (strlen($pgsql_host)) {
    try {
        echo "*** Connexion avec Gb_Db --> Pdo_Mysql\n";
        $test_tried++;
        $dbProt=new Gb_Db(array("type"=>"Pdo_Mysql",    "host"=>$mysql_host , "name"=>$mysql_name,      "user"=>$mysql_user, "pass"=>$mysql_pass, "port"=>$mysql_port));
        $date=$dbProt->retrieve_one("SELECT NOW()");
        print_r($date);
        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}



if (strlen($oci_host)) {
    try {
        echo "*** Connexion avec Zend_Db --> Pdo_oci\n";
        $test_tried++;
    //    $conn=Zend_Db::factory("Pdo_Oci", array("host"=>$oci_host, "port"=>$oci_port, , "username"=>$oci_user, "password"=>$oci_pass, "dbname"=>"(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$oci_host)(PORT=$oci_port))(CONNECT_DATA=(SERVICE_NAME=$oci_name)))"));
    //    $conn=Zend_Db::factory("Pdo_Oci", array("host"=>$oci_host, "port"=>$oci_port, , "username"=>$oci_user, "password"=>$oci_pass, "dbname"=>"(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$oci_host)(PORT=$oci_port))(CONNECT_DATA=(SID=$oci_name)))"));
        $conn=Zend_Db::factory("Pdo_Oci", array("host"=>$oci_host, "port"=>$oci_port, "username"=>$oci_user, "password"=>$oci_pass, "dbname"=>$oci_name));
        //$conn->getConnection();
        $stmt=$conn->query("SELECT SYSDATE FROM DUAL");
        $s=$stmt->fetch(Zend_Db::FETCH_ASSOC);
        print_r($s);
        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}


if (strlen($oci_host)) {
    try {
        echo "*** Connexion avec Zend_Db --> Oracle (extension oci8)\n";
        $test_tried++;
        $conn=Zend_Db::factory("Oracle", array("host"=>$oci_host, "username"=>$oci_user, "password"=>$oci_pass, "dbname"=>"(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$oci_host)(PORT=$oci_port))(CONNECT_DATA=(SID=$oci_name)))"));
        //$conn->getConnection();
        $stmt=$conn->query("SELECT SYSDATE FROM DUAL");
        $s=$stmt->fetch(Zend_Db::FETCH_ASSOC);
        print_r($s);
        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}


if (strlen($oci_host)) {
    try {
        echo "*** Connexion avec Gb_Db --> Pdo_Oci\n";
        $test_tried++;
        $dbProt=new Gb_Db(array("type"=>"Pdo_Oci",    "host"=>$oci_host , "port"=>$oci_port,   "name"=>$oci_name,      "user"=>$oci_user, "pass"=>$oci_pass));
        $date=$dbProt->retrieve_one("SELECT SYSDATE FROM DUAL");
        print_r($date);
        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}


if (strlen($mysql_host)) {
    try {
        echo "*** Connexion avec Gb_Db --> Pdo_Mysql\n";
        $test_tried++;
        $dbProt=new Gb_Db(array("type"=>"Pdo_Mysql",    "host"=>$mysql_host , "name"=>$mysql_name,      "user"=>$mysql_user, "pass"=>$mysql_pass, "port"=>$mysql_port));
        $date=$dbProt->retrieve_one("SELECT NOW()");
        print_r($date);
        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}


if (strlen($oci_host)) {
    try {
        echo "*** Connexion avec ocilogon (extention oci8)\n";
        $test_tried++;
        if (!function_exists("ocilogon")) {
            throw new Exception("oci8 extension not loaded.");
        }
        $conn_str="(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$oci_host)(PORT=$oci_port))(CONNECT_DATA=(SID=$oci_name)))";
        $hDbId=ocilogon($oci_user, $oci_pass, $conn_str);
        if ($hDbId === false) {
            throw new Exception("Unable to log in.");
        }
        ocilogoff($hDbId);
        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}

echo "FIN\n";
echo "$test_tried tests; $test_success success, $test_failed failed. Rate=".($test_success*100/$test_tried)."%\n";

