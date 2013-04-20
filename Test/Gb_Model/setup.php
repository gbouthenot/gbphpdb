<?php

use Gb\Model\Model;

function setup() {
    $db = new Gb_Db(array("type"=>"sqlite", "name"=>__DIR__.DIRECTORY_SEPARATOR."db.sqlite3"));
    Model::setAdapter($db);
}
