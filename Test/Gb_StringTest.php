<?php
require_once '../Gb/Exception.php';
require_once '../Gb/String.php';
/**
 * Gb_String test case.
 */
class Gb_StringTest extends PHPUnit_Framework_TestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        date_default_timezone_set("Europe/Paris");
        parent::setUp();
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









    /**
     * Tests Gb_String::getRevision()
     */
    public function testGetRevision() {
        $up = Gb_String::getRevision();
        $this->assertTrue(is_numeric($up));
        $this->assertTrue(is_int($up));
        $this->assertGreaterThan(184, $up);

        $up = Gb_String::getRevision(184);
        $this->assertTrue($up);

        $up = Gb_String::getRevision(999999, false);
        $this->assertFalse($up);

        try {
            Gb_String::getRevision(999999, true);
        } catch (Gb_Exception $e) {
            $e;
            return;
        }
        $this->fail();
    }

    /**
     * Tests Gb_String::str_to_time()
     */
    public function testStr_to_time() {
        $this->assertEquals(224850600, Gb_String::str_to_time("15/02/1977 11:30:00"));
        $this->assertEquals(224850600, Gb_String::str_to_time("1977-02-15 11:30:00"));
        try {
            Gb_String::str_to_time("1977-AA");
        } catch (Gb_Exception $e) {
            $e;
            return;
        }
        $this->fail();
    }

    /**
     * Tests Gb_String::random()
     */
    public function testRandom() {
        $up = Gb_String::random(5, "abc");
        $this->assertRegExp("#^[abc]{5}$#", $up);

        $up2 = Gb_String::random(5, "abc");
        $this->assertRegExp("#^[abc]{5}$#", $up2);
        $this->assertTrue($up !== $up2);

        $up = Gb_String::random(0, "abc");
        $this->assertEquals("", $up);

        $up = Gb_String::random(5, "a");
        $this->assertEquals("aaaaa", $up);

    }

    /**
     * Tests Gb_String::create_nom()
     */
    public function testCreate_nom() {
        $this->assertEquals("Anamaria",  Gb_String::create_nom("Anamaria Lufa"));
        $this->assertEquals("Ana Maria", Gb_String::create_nom("Ana Maria Lufa"));
    }

    /**
     * Tests Gb_String::mystrtoupper()
     */
    public function testMystrtoupper() {
        $up = "La forêt brûle ?";
        $up = Gb_String::mystrtoupper($up);
        $this->assertEquals("LA FORET BRULE ?", $up);
    }

    /**
     * Tests Gb_String::removeAccents()
     */
    public function testRemoveAccents() {
        $up = Gb_String::removeAccents("La cafetière a-t-elle coûté 5 € ?");
        $this->assertEquals("La cafetiere a-t-elle coute 5 EUR ?", $up);

        $up = Gb_String::removeAccents("ÁÀÂÄÃÅÇÉÈÊËÍÏÎÌÑÓÒÔÖÕÚÙÛÜÝáàâäãåçéèêëíìîïñóòôöõúùûüýÿ");
                   $this->assertEquals("AAAAAACEEEEIIIINOOOOOUUUUYaaaaaaceeeeiiiinooooouuuuyy", $up);

        $up = Gb_String::removeAccents('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ');
                   $this->assertEquals('aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY', $up);

        $up = Gb_String::removeAccents('^e ? "double"'." 'simple' ? ! * / \\ & &eacute;");
                   $this->assertEquals('^e ? "double"'." 'simple' ? ! * / \\ & &eacute;", $up);
    }

    /**
     * Tests Gb_String::date_fr()
     */
    public function testDate_fr() {
        $this->assertEquals("15/02/1977 11:30:00", Gb_String::date_fr("15/02/1977 11:30:00"));
        $this->assertEquals("15/02/1977 11:30:00", Gb_String::date_fr("1977-02-15 11:30:00"));
        $this->assertEquals("15/02/1977",          Gb_String::date_fr("15/02/1977"));
        $this->assertEquals("15/02/1977",          Gb_String::date_fr("1977-02-15"));
    }

    /**
     * Tests Gb_String::date_iso()
     */
    public function testDate_iso() {
        $this->assertEquals("1977-02-15 11:30:00", Gb_String::date_iso("15/02/1977 11:30:00"));
        $this->assertEquals("1977-02-15 11:30:00", Gb_String::date_iso("1977-02-15 11:30:00"));
        $this->assertEquals("1977-02-15",          Gb_String::date_iso("15/02/1977"));
        $this->assertEquals("1977-02-15",          Gb_String::date_iso("1977-02-15"));
    }

    /**
     * Tests Gb_String::date_isIntoInterval()
     */
    public function testDate_isIntoInterval() {
        $up = Gb_String::date_isIntoInterval("2001-01-01", "31/12/2001", "2000-02-02 15:44:00");
        $this->assertEquals(-1, $up);
        $up = Gb_String::date_isIntoInterval("2001-01-01", "31/12/2001", "2001-02-02 15:44:00");
        $this->assertEquals(0, $up);
        $up = Gb_String::date_isIntoInterval("2001-01-01", "31/12/2001", "2002-02-02 15:44:00");
        $this->assertEquals(1, $up);

        $up = Gb_String::date_isIntoInterval("2011-01-01", "31/12/2037");
        $this->assertEquals(0, $up);

    }

    /**
     * Tests Gb_String::explode()
     */
    public function testExplode() {
        $up = Gb_String::explode("/", "15/02/1977");
        $this->assertSame(array("15","02","1977"), $up);

        $up = Gb_String::explode("/", "15/02/1977", 2);
        $this->assertSame(array("15","02/1977"), $up);

        $up = Gb_String::explode("/", "");
        $this->assertSame(array(), $up);

    }

    /**
     * Tests Gb_String::arrayToCsv()
     */
    public function testArrayToCsv() {
        $up = Gb_String::arrayToCsv(array());
        $this->assertEquals("", $up);
        $up = Gb_String::arrayToCsv(array(array("a"=>1, "b"=>9)));
        $this->assertEquals("\"a\";\"b\";\n\"1\";\"9\";\n", $up);

        $up = Gb_String::arrayToCsv(array(array("a;x"=>"1\n2", "b"=>"9;\r\n8")));
        $this->assertEquals("\"a;x\";\"b\";\n\"1 - 2\";\"9; - 8\";\n", $up);

        $up = Gb_String::arrayToCsv(array(array("a"=>"1", "b"=>"2"), array("a"=>10, "b"=>20)));
        $this->assertEquals("\"a\";\"b\";\n\"1\";\"2\";\n\"10\";\"20\";\n", $up);

    }

    /**
     * Tests Gb_String::formatTime()
     */
    public function testFormatTime() {
        $up = Gb_String::formatTime(1);
        $this->assertEquals("1s", $up);
        $up = Gb_String::formatTime(2*60  +1);
        $this->assertEquals("2m 1s", $up);
        $up = Gb_String::formatTime(3*3600 + 2*60  +1);
        $this->assertEquals("3h 2m", $up);
        $up = Gb_String::formatTime(4*86400 + 3*3600 + 2*60  +1);
        $this->assertEquals("4d 3h", $up);
    }

    /**
     * Tests Gb_String::formatSize()
     */
    public function testFormatSize() {
        // TODO Auto-generated Gb_StringTest2::testFormatSize()
        $this->markTestIncomplete ( "formatSize test not implemented" );

        Gb_String::formatSize(/* parameters */);

    }

    /**
     * Tests Gb_String::formatTable()
     */
    public function testFormatTable() {
        // TODO Auto-generated Gb_StringTest2::testFormatTable()
        $this->markTestIncomplete ( "formatTable test not implemented" );

        Gb_String::formatTable(/* parameters */);

    }

    /**
     * Tests Gb_String::appendPrepend()
     */
    public function testAppendPrepend() {
        // TODO Auto-generated Gb_StringTest2::testAppendPrepend()
        $this->markTestIncomplete ( "appendPrepend test not implemented" );

        Gb_String::appendPrepend(/* parameters */);

    }

    /**
     * Tests Gb_String::realpath()
     */
    public function testRealpath() {
        // TODO Auto-generated Gb_StringTest2::testRealpath()
        $this->markTestIncomplete ( "realpath test not implemented" );

        Gb_String::realpath(/* parameters */);

    }

}

