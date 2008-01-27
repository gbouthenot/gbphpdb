<?php
//require_once("Zend/Exception.php");
require_once("Zend/Loader.php");
require_once("Gb/Util.php");

Gb_Util::$debug=1;
Gb_Response::$noFooterEscape=1;

Gb_Log::$loglevel_showuser=Gb_Log::LOG_ALL;
Gb_Log::$loglevel_file=Gb_Log::LOG_NOTICE;
Gb_Log::$loglevel_footer=Gb_Log::LOG_ALL;
Gb_Log::$logFilename="demo.log";

Gb_Util::startup();

function main()
{
	Gb_Response::$head["author"]=array("name", "Gilles Bouthenot");
	Gb_Response::send_headers(1);
	
	echo "this is main\n";
	
	echo "url is: ".$_SERVER['PHP_SELF'].Gb_Util::url_debug("&")."\n";

	Gb_Log::log(Gb_Log::LOG_DEBUG, "*** FOOTER ***:");


	$c=array(0=>"a", "b"=>array(0=>"c", "d"=>"e"));
	$d=array("a", "b", "c", 3=>"d", 5=>"e");

	//demo de dump:
	Gb_Log::log(Gb_Log::LOG_DEBUG, "début des tests : dump");
	$ret=Gb_Log::dump($d);	                                  echo ($ret=="array('a', 'b', 'c', 'd', 5=>'e')"  ?"OK":"ERROR")." dump array: $ret\n";
	$ret=Gb_Log::dump($c);                                    echo ($ret=="array('a', b=>array('c', d=>'e'))"  ?"OK":"ERROR")." dump array of array: $ret\n";
	$ret=Gb_Log::dump("toto");                                echo ($ret=="'toto'"                             ?"OK":"ERROR")." dump string: $ret\n";

	//demo de call
	Gb_Log::log(Gb_Log::LOG_DEBUG, "début des tests : call");
	$ret=Gb_Log::log_function(Gb_Log::LOG_DEBUG, "", array("Gb_Log","dump"), array($d));	     echo ($ret=="array('a', 'b', 'c', 'd', 5=>'e')"  ?"OK":"ERROR")." call of static function: $ret\n";

	$b="myB"; $c=array(5=>'my5', 'toto'=>'myToto');
	$ret=Gb_Log::log_function(Gb_Log::LOG_DEBUG, "", "testfunction", array("myA", $b, &$c)); echo ($ret=="a=myA b=myB c[5]=my5 c[toto]=myToto"?"OK":"ERROR")." call: $ret\n";
	                                                         echo ($b=='myB'&&$c[5]=="new5"                   ?"OK":"ERROR")." args passed by reference: b=$b c[5]=".$c[5]."\n";

	$catched=0;
	try
	{
		$c=array(5=>'my5', 'toto'=>'myToto');
		Gb_Log::log_function(Gb_Log::LOG_DEBUG, "", "testfunction3", array("myA", "myB", &$c));
		echo "b=$b c[5]=".$c[5]."\n";
	}catch (Gb_Exception $e)
	{ $catched=1;
	}
	                                                         echo ($catched==1                                ?"OK":"ERROR")." exception thrown\n";

	//demo de Gb_Timer
	Gb_Log::log(Gb_Log::LOG_DEBUG, "début des tests : Gb_Timer");
	$t1=new Gb_Timer("Timer A");
	$t2=new Gb_Timer();
	$t3=new Gb_Timer();
	

	$ret1=$t1->get();$ret2=$t2->get();$ret3=$t3->get(null);  echo ($ret1>0&&$ret1<1&&$ret2>0&&$ret2<1&&$ret3>0&&$ret3<1?"OK":"ERROR")." t1: $ret1 t2: $t2 t3: $t3\n";
	$t1->logTimer();
	$t3->pause();
	sleep(1);
	$ret1=$t1->get();$ret2=$t2->get();$ret3=$t3->get(null);  echo ($ret1>1&&$ret1<2&&$ret2>1&&$ret2<2&&$ret3>0&&$ret3<1?"OK":"ERROR")." t3->pause t1: $t1 t2: $t2 t3: $ret3\n";
	$t2->logTimer();
	$t2->reset();
	$t3->resume();
	$ret1=$t1->get();$ret2=$t2->get();$ret3=$t3->get(null);  echo ($ret1>1&&$ret1<2&&$ret2>0&&$ret2<1&&$ret3>0&&$ret3<1?"OK":"ERROR")." t2->reset, t3->resume t1: $ret1 t2: $ret2 t3: $t3\n";

	//démo de Gb_Util::roundCeil
	Gb_Log::log(Gb_Log::LOG_DEBUG, "début des tests : roundCeil");
	$ret=Gb_Util::roundCeil(120000  ,2);   echo ("$ret"==="120000"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(120000.1,2);   echo ("$ret"==="130000"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(129999.9,2);   echo ("$ret"==="130000"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(120000  ,3);   echo ("$ret"==="120000"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(120000.1,3);   echo ("$ret"==="121000"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(129999.9,3);   echo ("$ret"==="130000"  ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(1400,2);       echo ("$ret"==="1400"    ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(1400.01,2);    echo ("$ret"==="1500"    ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(1400,3);       echo ("$ret"==="1400"    ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(1400.01,3);    echo ("$ret"==="1410"    ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(140,2);        echo ("$ret"==="140"     ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(140.01,2);     echo ("$ret"==="150"     ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(140,3);        echo ("$ret"==="140"     ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(140.01,3);     echo ("$ret"==="141"     ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(14,2);         echo ("$ret"==="14"      ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(14.01,2);      echo ("$ret"==="15"      ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(14,3);         echo ("$ret"==="14"      ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(14.01,3);      echo ("$ret"==="14.1"    ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(1.4,2);        echo ("$ret"==="1.4"     ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(1.401,2);      echo ("$ret"==="1.5"     ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(1.4,3);        echo ("$ret"==="1.4"     ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(1.401,3);      echo ("$ret"==="1.41"    ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(.14,2);        echo ("$ret"==="0.14"    ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.1401,2);      echo ("$ret"==="0.15"    ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.14,3);        echo ("$ret"==="0.14"    ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.1401,3);      echo ("$ret"==="0.141"   ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(.014,2);       echo ("$ret"==="0.014"   ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.01401,2);     echo ("$ret"==="0.015"   ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.014,3);       echo ("$ret"==="0.014"   ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.01401,3);     echo ("$ret"==="0.0141"  ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(.0014,2);      echo ("$ret"==="0.0014"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.001401,2);    echo ("$ret"==="0.0015"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.0014,3);      echo ("$ret"==="0.0014"  ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.001401,3);    echo ("$ret"==="0.00141" ?"OK":"ERROR")." $ret\n";
	
	$ret=Gb_Util::roundCeil(.00014,2);     echo ("$ret"==="0.00014" ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.0001401,2);   echo ("$ret"==="0.00015" ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.00014,3);     echo ("$ret"==="0.00014" ?"OK":"ERROR")." $ret\n";
	$ret=Gb_Util::roundCeil(.0001401,3);   echo ("$ret"==="0.000141"?"OK":"ERROR")." $ret\n";
	

	$t3->reset();
	$t3->logTimer(Gb_Log::LOG_DEBUG, "reset");


	Gb_Log::log(Gb_Log::LOG_DEBUG, "début des tests : Gb_Db");
	$dbGE=new Gb_Db(array("driver"=>"Pdo_Mysql", "host"=>"5.157.252.127", "user"=>"test", "pass"=>"***REMOVED***", "name"=>"horde"));
	$t3->logTimer(Gb_Log::LOG_DEBUG, "new Gb_Db");
	
	$dbGE->beginTransaction();
//	$dbGE->delete("horde_prefs", array());
//	$dbGE->insert("horde_prefs", array("pref_uid"=>"root", "pref_scope"=>"scope1", "pref_name"=>"name1", "pref_value"=>"value1"));
//	$dbGE->update("horde_prefs", array("pref_value"=>"toto"), array());
//todo:escape
// $dbGE->update("horde_prefs", array("pref_value"=>"toto"), array("pref_name=".$dbGE->quote("last_login));

//	Doit générer une exception si on n'a pas fait delete:
//	$dbGE->replace("horde_prefs", array("pref_value"=>"toto"), array($dbGE->quoteInto("pref_name=?","last_login")));

//	$dbGE->replace("horde_prefs", array("pref_value"=>"toto", "pref_scope"=>"scope2"), array("pref_uid='root'", "pref_name='last_login'"));
	
	$sql="select * FROM horde_prefs WHERE pref_uid=?";
	$sqlparam=array('root');
	
	$dbGE->retrieve_all($sql, $sqlparam);
	$t3->logTimer(Gb_Log::LOG_DEBUG, "retrieve_all");

	$r=Gb_Log::log_function(Gb_Log::LOG_DEBUG, "", array($dbGE, "retrieve_all"), array($sql, $sqlparam));
	echo "<pre>".print_r($r, true)."</pre>";

	$r=Gb_Log::log_function(Gb_Log::LOG_DEBUG, "", array($dbGE, "retrieve_all"), array($sql, $sqlparam, "pref_name"));
	echo "<pre>".print_r($r, true)."</pre>";

	$r=Gb_Log::log_function(Gb_Log::LOG_DEBUG, "", array($dbGE, "retrieve_all"), array($sql, $sqlparam, "pref_name", "pref_value"));
	echo "<pre>".print_r($r, true)."</pre>";

	$dbGE->rollBack();


	return "OK";
}

echo "Toujours là !";


function testfunction($a='', $b='', &$c='')
{
	$ret="a=$a b=$b c[5]=".$c[5]." c[toto]=".$c['toto'];
	$b="newB";
	$c[5]="new5";
	return $ret;
}
