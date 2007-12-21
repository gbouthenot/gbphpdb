#!/usr/bin/php
<?php

//require_once("Zend/Db.php");
require_once("Gb/Db.php");
require_once("Gb/Response.php");

//if ( $GLOBALS['argc']==2 && $GLOBALS['argv'][1]=='commit' )
//	echo "OK";

//exit(1);



/*
echo "Connexion avec Zend_Db --> Oracle\n";
$conn=Zend_Db::factory("Oracle", array("host"=>"172.26.12.3", "username"=>"gbouthenot", "password"=>"***REMOVED***", "dbname"=>"(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=172.26.12.3)(PORT=1521))(CONNECT_DATA=(SID=PROT)))"));
//$conn->getConnection();
$stmt=$conn->query("SELECT SYSDATE FROM DUAL");
$s=$stmt->fetch(Zend_Db::FETCH_ASSOC);
print_r($s);
echo "Fin de connexion\n";
*/

/*
echo "Connexion avec GbUtilDb --> Oracle\n";
$dbProt=new GbUtilDb(array("type"=>"Oracle",    "host"=>"172.26.12.3" , "name"=>"PROT",      "user"=>"gbouthenot", "pass"=>"***REMOVED***"));
$dbProt->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");
$date=$dbProt->retrieve_one("SELECT SYSDATE FROM DUAL", array());
$date=$dbProt->retrieve_one("SELECT DATE_NAISSANCE FROM USAGER WHERE NOM_USUEL='BOUTHENOT'", array());
print_r($date);
echo "Fin de connexion\n";
*/

echo "Connexion avec Gb_Db --> Pdo_Mysql\n";
$dbProt=new Gb_Db(array("type"=>"Pdo_Mysql",    "host"=>"localhost" , "name"=>"gestion_e",      "user"=>"gestion_e", "pass"=>"***REMOVED***"));
$date=$dbProt->retrieve_one("SELECT NOW()");
print_r($date);
//print_r($dbProt->getTablesName());
echo "Fin de connexion\n";

echo "Connexion avec Gb_Db --> Pdo_Oci\n";
$dbProt=new Gb_Db(array("type"=>"Pdo_Oci",    "host"=>"172.26.12.3" , "name"=>"PROT",      "user"=>"gbouthenot", "pass"=>"***REMOVED***"));
$date=$dbProt->retrieve_one("SELECT SYSDATE FROM DUAL");
print_r($date);
print_r($dbProt->getTablesDesc());
echo "Fin de connexion\n";

echo "FIN\n";

