#!/usr/bin/php
<?php

set_include_path(dirname(__FILE__));

$oci_host=$mysql_host=$pgsql_host=$oci_sql="";
$use_Zend=$use_Gb=$use_Oci=$use_Pdo=false;

$oci_host="apogee-bd.univ-fcomte.fr";
$oci_port=1524;
$oci_user="gbouthen";
$oci_pass="********";
$oci_name="APOTEST";
$oci_charset = 'UTF8'; // AL32UTF8 / UTF8
$oci_sql     = 'SELECT * FROM APOGEE.COMP_LNG_CAD WHERE COD_IND=130298';

$use_Zend = true;
$use_Gb   = true;
$use_Oci  = true;
$use_Pdo  = true;

/*
$mysql_host="localhost";
$mysql_port=3306;
$mysql_user="root";
$mysql_pass="********";
$mysql_name="mysql";
*/

/*
$pgsql_host="localhost";
$pgsql_port=5432;
$pgsql_user="ticeval";
$pgsql_pass="********";
$pgsql_name="postgres";
*/


if ($use_Gb) {
    require_once("Gb/Db.php");
}

if ($use_Zend) {
  require_once("Zend/Db.php");
}


$test_failed=$test_success=$test_tried=0;

if ($use_Zend && $use_Pdo && strlen($pgsql_host)) {
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



if ($use_Gb && $use_Pdo && strlen($pgsql_host)) {
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



if ($use_Zend && $use_Pdo && strlen($oci_host)) {
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


if ($use_Zend && $use_Oci && strlen($oci_host)) {
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


if ($use_Gb && $use_Pdo && strlen($oci_host)) {
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


if ($use_Gb && $use_Pdo && strlen($mysql_host)) {
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


if ($use_Oci && strlen($oci_host)) {
    try {
        echo "*** Connexion avec ocilogon (extention oci8)\n";
        $test_tried++;
        if (!function_exists("ocilogon")) {
            throw new Exception("oci8 extension not loaded.");
        }
        $conn_str="(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$oci_host)(PORT=$oci_port))(CONNECT_DATA=(SID=$oci_name)))";
        $hDbId=@ocilogon($oci_user, $oci_pass, $conn_str);
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



if ($use_Oci && strlen($oci_host) && strlen($oci_sql)) {
    try {
        echo "*** Connexion avec ociconnect (extention oci8), requete sql complete\n";
        $test_tried++;
        if (!function_exists("ocilogon")) {
            throw new Exception("oci8 extension not loaded.");
        }


        $tns = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = $oci_host)(PORT = $oci_port)))(CONNECT_DATA=(SID=$oci_name)))";
        $conn = oci_connect($oci_user, $oci_pass, $tns, $oci_charset);
        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        $stid = oci_parse($conn, $oci_sql);
        oci_execute($stid);

        echo "<table border='1'>\n";
        while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
            echo "<tr>\n";
            foreach ($row as $item) {
                echo "    <td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "") . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";


        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}


if ($use_Gb && $use_Pdo && strlen($oci_host) && strlen($oci_sql)) {
    try {
        echo "*** Connexion avec Gb_Db (Pdo_Oci8), requete sql complete\n";
        $test_tried++;
        if (!function_exists("ocilogon")) {
            throw new Exception("oci8 extension not loaded.");
        }


        $params = array("type"=>"Pdo_Oci", "user"=>$oci_user, "pass"=>$oci_pass, "port"=>$oci_port, "host"=>$oci_host, "name"=>$oci_name, "charset"=>$oci_charset);
        $db = new Gb_Db($params);
        $all = $db->retrieve_all($oci_sql);
        print_r($all);


        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}


if ($use_Gb && $use_Pdo && strlen($oci_host) && strlen($oci_sql)) {
    try {
        echo "*** Connexion avec Gb_Db (oci8), requete sql complete\n";
        $test_tried++;
        if (!function_exists("ocilogon")) {
            throw new Exception("oci8 extension not loaded.");
        }


        $params = array("type"=>"Oracle_Oci8", "user"=>$oci_user, "pass"=>$oci_pass, "port"=>$oci_port, "host"=>$oci_host, "name"=>$oci_name, "charset"=>$oci_charset);
        $db = new Gb_Db($params);
        $all = $db->retrieve_all($oci_sql);
        print_r($all);


        echo "*** Ok\n\n";
        $test_success++;
    } catch (Exception $e) {
        echo "EXCEPTION: $e\n\n";
        $test_failed++;
    }
}

echo "FIN\n";
echo "$test_tried tests; $test_success success, $test_failed failed. Rate=".($test_success*100/$test_tried)."%\n";
