<?php


require_once "../Gb/Db.php";
require_once "../Gb/Model.php";
require_once "../Gb/Model/Rows.php";
require_once "../Gb/Model/Row.php";

require_once 'Gb_Model/setup.php';
require_once 'Gb_Model/models.php';


class Gb_ModelTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Gb_Timer
     */
    private $Gb_Timer;


    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        setup();
    }


    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }


    /**
     * Constructs the test case.
     */
    public function __construct()
    {
        // TODO Auto-generated constructor
    }


    public function testGetAll()
    {
        $authors = Author::getAll();
        $this->assertSame(60, count($authors));
        $this->assertSame("ibach", $authors->{49}->login);

        $aAuthors = JSON_decode((string) $authors, true);
        $this->assertSame(60, count($aAuthors));
        $this->assertSame("cbassand", $aAuthors[45]["login"]);

        // test foreach
        $found = false;
        foreach ($authors as $key=>$author) {
            if (43 === $key) {
                $found = $author->login; break;
            }
        }
        $this->assertSame("cbarbie6", $found);
    }

    public function testGetSome()
    {
        $authors = Author::getSome(array(45, 49, 43));
        $this->assertSame(3, count($authors));
        $this->assertSame("ibach", $authors->{49}->login);

        $aAuthors = JSON_decode((string) $authors, true);
        $this->assertSame(3, count($aAuthors));
        $this->assertSame("cbassand", $aAuthors[45]["login"]);

        // test foreach
        $found = false;
        foreach ($authors as $key=>$author) {
            if (43 === $key) {
                $found = $author->login; break;
            }
        }
        $this->assertSame("cbarbie6", $found);

        // inexistent row should throw exception
        $e = null;
        try {
            $authors->{-1};
        } catch (Exception $e) {
        }
        $this->assertSame("Gb_Exception", get_class($e));

        // inexistent row should throw exception
        $e = null;
        try {
            $authors[-1];
        } catch (Exception $e) {
        }
        $this->assertSame("Gb_Exception", get_class($e));

    }

    public function testGetSome_extended() {
        // fetch a row twice
        $authors = Author::getSome(array(45, 49, 43, 45));
        $this->assertSame(3, count($authors));

        // fetch an inexistent row
        $authors = Author::getSome(array(45, 49, 43, -1));
        $this->assertSame(3, count($authors));

        $authors = Author::getSome(array());
        $this->assertSame(0, count($authors));

    }


    public function testGetOne()
    {
        $author = Author::getOne(37);
        $this->assertSame("itraore2", $author->login);

        // inexistent row should throw exception
        $e = null;
        try {
            Author::getOne(-1);
        } catch (Exception $e) {
        }
        $this->assertSame("Gb_Exception", get_class($e));

    }



    public function testUnknownRel()
    {
        $etudiant = Author::getOne(3);
        $etudiants = Author::getSome(array(1,2));

        // inexistent relation should throw exception
        $e = null;
        try {
            $etudiant->rel("*invalid*");
        } catch (Exception $e) {
        }
        $this->assertSame("Gb_Exception", get_class($e));

        // inexistent relation should throw exception
        $e = null;
        try {
            $etudiants->rel("*invalid*");
        } catch (Exception $e) {
        }
        $this->assertSame("Gb_Exception", get_class($e));

    }



    public function testGetSomeBelongsto()
    {
        $questionnaires = Questionnaire::getSome(array(24,37,22));
        $this->assertEquals( 8, $questionnaires->{24}->etudiant_id);
        $this->assertEquals(19, $questionnaires[37]->etudiant_id);

        $etudiants = $questionnaires->rel("etudiant");
        $this->assertSame("bcael2",   $etudiants->{8}->login);
        $this->assertSame("cguille8", $etudiants[19]->login);

        // check that the relation is automatically copied
        $questionnaire = $questionnaires[22];
        $reflection = new ReflectionClass('\Gb\Model\Row');
        $prop = $reflection->getProperty('rel');
        $prop->setAccessible(true);
        $aRels = $prop->getValue($questionnaire);
        $this->assertSame("vlave", $aRels["etudiant"]["login"]);

        $this->assertSame("bcael2",   $questionnaires->rel("etudiant")->{8}->login);
        $this->assertSame("cguille8", $questionnaires->rel("etudiant")[19]->login);
    }

    public function testGetOneBelongsto()
    {
        $questionnaire = Questionnaire::getOne(20);
        $this->assertEquals(10, $questionnaire->etudiant_id);
        $this->assertSame("vbassano", $questionnaire->rel("etudiant")->login);
    }


    public function testFindAll()
    {
        $questionnaires = Questionnaire::findAll(array("etudiant_id"=>1));
        $this->assertSame(7, count($questionnaires));
        foreach ($questionnaires as $questionnaire) {
            if ($questionnaire->etudiant_id !== '1') {
                throw new Exception("error findAll");
            }
        }
    }




    public function testGetOneHasmany()
    {
        // this student only have one questionnaire
        $etudiant = Author::getOne(3);
        $questionnaires = $etudiant->rel("questionnaires");
        $this->assertEquals(1, count($questionnaires));

        // this one has 3 questionnaires
        $questionnaires = Author::getOne(4)->rel("questionnaires");
        $this->assertEquals(3, count($questionnaires));
    }



    public function testGetSomeHasmany() {
        $etudiants = Author::getSome(array(6,7));
        $questionnaires = $etudiants->rel("questionnaires");
        $this->assertEquals(8, count($questionnaires));

        // check that the relation is automatically copied
        $etudiant = $etudiants[6];
        $reflection = new ReflectionClass('\Gb\Model\Row');
        $prop = $reflection->getProperty('rel');
        $prop->setAccessible(true);
        $aRels = $prop->getValue($etudiant);
        $this->assertEquals(7, count($aRels["questionnaires"]));
        $this->assertEquals(7, count($etudiant->rel("questionnaires")));

    }


    public function testBelongstojson() {
        $questionnaire = Questionnaire::getOne(2);
        $alineas = $questionnaire->rel("alineas");      // 14,15,21,22,29,31,36
        $this->assertEquals(7, count($alineas));
        $this->assertEquals(24, $alineas[29]->question_id);
        $this->assertEquals(26, $questionnaire->rel("alineas")->{31}->question_id);
    }


}

