<?php 

error_reporting(E_ALL|E_WARNING);


require_once 'Zend/Loader.php';
spl_autoload_register(array('Zend_Loader', 'autoload'));


echo "<script src='http://www2.fcomte.iufm.fr/gestion_e/js/combined.js?v=1'         type='text/javascript'></script>\n";
echo "<style>
.GBFORM .OK{background:#0f0;}.GBFORM .NOK{background:#f00;}
fieldset{background:#ccc; zborder:1px solid #000;}
fieldset:hover        {background:#ddd; }
fieldset:hover legend {background:#eee; text-align:center;}
legend{border:1px solid #888; -moz-border-radius:25px; background:#ddd;}
</style>\n";

$e1=new Gb_Form_Elem_Text("TEXT1");
$e1
 ->inInput("size='40'")
 ->regexp("/^[0-9a-z]{4}\$/")
 ->preInput("Name : ")
 ->postInput("(case-sensitive)")
 ->minValue(1234)
 ->notValue(array("2345", "3456", "abcd", "GBFORM_GROUP1_TEXT2"))
 ->publicName("Deuxième nom")
;

$e2=new Gb_Form_Elem_Hidden("HIDDEN1", array("container"=>"div"));
$e4=new Gb_Form_Elem_Hidden("HIDDEN2", array());
$e5=new Gb_Form_Elem_Hidden("HIDDEN3", array());
$e6=new Gb_Form_Elem_Hidden("HIDDEN4", array());
$e7=new Gb_Form_Elem_Select("g2sel1",  array("args"=>array(array('false', "Choix?"), array(1, "Choix1"), "default"=>array(2, "Choix2"), array(3, "Choix3"))));

$e3=new Gb_Form_Elem_Text("TEXT2", array("postInput"=>"Votre nom"));
$e3
 ->inInput("size='40'")
 ->regexp("/^[0-9a-z]{4}\$/")
 ->preInput("Text2 : ")
 ->minValue(array(1234, "GBFORM_GROUP1_TEXT1"))
 ->notValue(array("2345", "3456", "abcd", "GBFORM_GROUP1_TEXT1"))
 ->errorMsg("Message d'erreur")
 ->publicName("Premier nom")
;

$es1=new Gb_Form_Elem_Submit("SUBMIT1");

$group1=new Gb_Form_Group(array("preGroup"=>"<fieldset><legend>GROUP1</legend>", "postGroup"=>"</fieldset>"), array("classStatut"=>"%s", "name"=>"GROUP1_%s"));
$group1->append($e2,$e3);
$group1->elem1=$e1;

$group2=new Gb_Form_Group(array(), array("name"=>"GROUP2_%s"));
$group2
 ->append($e5, $e6)
 ->append(new Gb_Form_Elem_Password("PASS1"))
 ->preGroup("<fieldset><legend>GROUP2</legend>")
 ->postGroup("</fieldset>")
 ->append($e7->fMandatory(true));
;


$form1=new Gb_Form2();
$form1->append($e4);
$form1->group1=$group1;
$form1->group1->append($group2);
$form1->append($es1);

$group1->elem1->value("TEST2");

//$it=new RecursiveIteratorIterator($form1);
//foreach ($form1 as $k=>$e) {
//    echo "$k:".htmlentities($e);
//}
echo "<script type='text/javascript'>";
echo $form1->renderJavascript();
echo "</script>\n<form method='post' action='Form2.php'><div class='GBFORM'>";
$form1->getFromPost();
if ($form1->isPost()) {
    $errs=$form1->validate();
    if ($errs!==true) { echo "Erreur:".implode("<br />", $errs);}
}
echo $form1->renderHtml()."</div>\n<pre>\n";
echo htmlspecialchars($form1->renderHtml());
echo htmlspecialchars($form1->renderJavascript());
