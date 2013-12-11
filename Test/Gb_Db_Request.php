<?php
require_once '../Gb/Db/Engine.php';

$starttime=microtime(true);

$dbGE=new Gb_Db(array("type"=>"Pdo_Mysql", "host"=>"gestion_e_db" ,   "name"=>"gestion_e_test", "user"=>"gestion_e",  "pass"=>"***REMOVED***"));
$dbProt=new Gb_Db(array("type"=>"Pdo_Oci", "host"=>"172.26.12.3" ,    "name"=>"PROT",           "user"=>"gbouthenot", "pass"=>"***REMOVED***"));
$req=new Gb_Db_Engine();

$req
    ->setAlias("ge", $dbGE)
    ->setAlias("prot", $dbProt)
;

$res=testJoin($req);

echo Gb_String::formatTable($res, "text");
echo "returned ".count($res)." rows\n";

echo "End. Time=".(microtime(true)-$starttime)." s memory=".(memory_get_peak_usage()/1024)." Kib ".Gb_Db::GbResponsePlugin()."\n";

function testJoin(Gb_Db_Engine $req)
{
    $code=array(
                   "LEFT JOIN",
                        array("SELECT", "ge", "
                               SELECT usa_idprothee FROM tusager LEFT JOIN tvoeux_admission ON vad_usa_login=usa_login WHERE (usa_statut='PE0' OR usa_statut='PLC0') AND vad_usa_login IS NULL
                              "),
                        array("SELECT", "prot", "
                               SELECT COUNT(*) FROM PROTSCOL.CANDIDATURE
                               WHERE DOSSIER_RECEVABLE='O' and (AVIS_COMMISSION='1' or AVIS_COMMISSION='C') AND NO_DOSSIER=##usa_idprothee## AND ANNEE=**Année**
                              "),
                   );

    $res=$req->execute($code, array("Année"=>2009));
    return $res;
}

function testMemJoin(Gb_Db_Engine $req)
{
    $code=array(
                   "LEFT MEMJOIN",
                        array("SELECT", "ge", "
                               SELECT usa_idprothee FROM tusager LEFT JOIN tvoeux_admission ON vad_usa_login=usa_login WHERE (usa_statut='PE0' OR usa_statut='PLC0') AND vad_usa_login IS NULL
                              "),
                        array("SELECT", "prot", "
                               SELECT NO_DOSSIER, COUNT(*) FROM PROTSCOL.CANDIDATURE
                               WHERE DOSSIER_RECEVABLE='O' and (AVIS_COMMISSION='1' or AVIS_COMMISSION='C') AND ANNEE=**Année**
                               GROUP BY NO_DOSSIER
                              "),
                        array("usa_idprothee"=>"NO_DOSSIER")
                   );
    $res=$req->execute($code, array("Année"=>2009));
    return $res;
}
