<?php

require_once("../Gb/Db.php");
require_once("../Gb/Response.php");
require_once("../Gb/Util.php");

Gb_Response::$nologo=true;
Gb_Util::$debug=false;

Gb_Util::startup();

function main()
{
    $db=new Gb_Db(array("type"=>"Pdo_Mysql",    "host"=>"localhost" , "name"=>"gestion_e",      "user"=>"gestion_e", "pass"=>"***REMOVED***"));
    $users=$db->retrieve_all("SELECT usa_login, usa_passwordmd5std FROM tusager WHERE usa_statut='ADM'", array(), "usa_login", "usa_passwordmd5std");

    foreach ($users as $login=>$password) {
        // converti le codage du mot de passe de hexa à base64
        $password=base64_encode(pack("H*", $password));
        echo "dn: cn=$login,dc=fcomte,dc=iufm,dc=lan\n";
    	echo "objectClass: person\n";
        echo "cn: $login\n";
    	echo "sn: $login\n";
    	echo "userPassword: {MD5}$password\n";
    	echo "radiusLoginLATGroup: Iufm\n";
    	echo "\n";
    }


}



?>
