<?php

use Gb\Model;

class Author extends Model\Model {

    static $_tablename = "authors";
    static $_pk        = "id";
    static $rels = array(
        'questionnaires'=>array("reltype"=>"has_many", "class_name"=>"Questionnaire", "foreign_key"=>"etudiant_id"),
    );

}
class QuestionAlinea extends Model\Model {

    static $_tablename = "question_alineas";
    static $_pk        = "id";

}

class Question extends Model\Model {

    static $_tablename = "questions";
    static $_pk        = "id";

}

class Questionnaire extends Model\Model {

    static $_tablename = "questionnaires";
    static $_pk        = "id";

    static $rels = array(
        'etudiant'=>array("reltype"=>"belongs_to", "class_name"=>"Author", "foreign_key"=>"etudiant_id"),
    );
}
