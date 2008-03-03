<?php
require_once 'Gb/String.php';
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
     * Tests Gb_String::create_nom()
     */
    public function testCreate_nom()
    {
        $this->assertEquals("Anamaria",  Gb_String::create_nom("Anamaria Lufa"));
        $this->assertEquals("Ana Maria", Gb_String::create_nom("Ana Maria Lufa"));
    }


    /**
     * Tests Gb_String::date_fr()
     */
    public function testDate_fr()
    {
        $this->assertEquals("15/02/1977 11:30:00", Gb_String::date_fr("15/02/1977 11:30:00"));
        $this->assertEquals("15/02/1977 11:30:00", Gb_String::date_fr("1977-02-15 11:30:00"));
        $this->assertEquals("15/02/1977",          Gb_String::date_fr("15/02/1977"));
        $this->assertEquals("15/02/1977",          Gb_String::date_fr("1977-02-15"));
    }


    /**
     * Tests Gb_String::date_iso()
     */
    public function testDate_iso()
    {
        $this->assertEquals("1977-02-15 11:30:00", Gb_String::date_iso("15/02/1977 11:30:00"));
        $this->assertEquals("1977-02-15 11:30:00", Gb_String::date_iso("1977-02-15 11:30:00"));
        $this->assertEquals("1977-02-15",          Gb_String::date_iso("15/02/1977"));
        $this->assertEquals("1977-02-15",          Gb_String::date_iso("1977-02-15"));
    }


    /**
     * Tests Gb_String::mystrtoupper()
     */
    public function testMystrtoupper()
    {
        $this->assertEquals("LA FORET BRULE", Gb_String::mystrtoupper("La forêt brûle"));
        $this->assertEquals(
                                    "' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`ABCDEFGHIJKLMNOPQRSTUVWXYZ{|}~€?,F,_†‡ˆ%S‹O?Z??''“”.--˜TS›O?ZY IC£¤¥|§¨C2<¬­R¯°±23'UQ.¸10>¼½¾?AAAAAAACEEEEIIIIDNOOOOOXOUUUUYþBAAAAAAACEEEEIIIIONOOOOO/OUUUUYþ",
            Gb_String::mystrtoupper("' !#%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~€?‚ƒ„…†‡ˆ‰Š‹Œ?Ž??‘’“”•–—˜™š›œ?žŸ ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ")
            );
    }


    /**
     * Tests Gb_String::str_to_time()
     */
    public function testStr_to_time()
    {
        $this->assertEquals(224850600, Gb_String::str_to_time("15/02/1977 11:30:00"));
        $this->assertEquals(224850600, Gb_String::str_to_time("1977-02-15 11:30:00"));
        try {
            Gb_String::str_to_time("1977-02-15");
        } catch (Gb_Exception $e) {
            $e;
            return;
        }
        $this->fail();
    }
}

