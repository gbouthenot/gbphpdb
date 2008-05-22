<?php

error_reporting(E_ALL | E_STRICT);

require_once("Gb/Form.php");

// création d'un formulaire non associé à une base de données: pas de paramètres dans Gb_Form()
$myForm=new Gb_Form();

// ajoute un champ
$myForm->addElement(
    "NOM",
    array(
        "type"=>"TEXT",
        "fMandatory"=>true,
        "args"=>array(
            "regexp"=>"/^a.{1,3}$/",
    ),
    )
);

// champ mais avec des personnalisations
$myForm->addElement(
    "PRIX",
    array(
        "type"=>"TEXT",
        "fMandatory"=>true,
        "args"=>array(
            "regexp"=>"/^[0-9]{3}$/",
            "minvalue"=>"111",
            "maxvalue"=>"173",
            "notvalue"=>"150",
        ),
        "invalidMsg"=>"Veuillez entrer un nombre entre 111 et 173, et différent de 150",
        "preInput"=>"Prix de référence: ",
        "postInput"=>"€",
        "inInput"=>"maxlength='3' size='3' autocomplete='off'",
    )
);

$myForm->addElement(
    "PAYS",
    array(
        "type"=>"SELECT",
        "fMandatory"=>false,
        "args"=>array(
            array("optgroup", "Amérique"), array("USA"), array("Canada"), array("optgroup", "Europe"), "default"=>array('France'), array("Belgique"),
        ),
    )
);

$result="";
if ($myForm->process()===true) {
    $result="Formulaire valide";
}
?>

<html>
<head>
    <script src='http://pollux3.fcomte.iufm.fr/gbo/neon/gestion_e_mvc/js/prototype.js' type='text/javascript'></script>
    <script src='http://pollux3.fcomte.iufm.fr/gbo/neon/gestion_e_mvc/js/gb.js'        type='text/javascript'></script>
    <style type='text/css'>
        .GBFORM .OK          {}
        .GBFORM .NOK         {border:1px solid #f00;}
        .GBFORM .ERROR       {background:#000; color:#f00; display:inline;}
    </style>
    <script type='text/javascript'>
        <?= $myForm->getJavascript(); ?>
    </script>
</head>
<body>
    <form action="gb_form_demo.php" method="post">
        <?= $myForm->getHtml() ?>
        <input type='submit' />
        <br />
        <?= $result ?>
    </form>
</body>
</html>

