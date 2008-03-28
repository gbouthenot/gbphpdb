<?php
require_once 'Gb/Util.php';
/**
 * Gb_Util test case.
 */
class Gb_UtilTest extends PHPUnit_Framework_TestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        Gb_Glue::$projectName="testProject";
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
     * Tests Gb_Util::array_merge()
     */
    public function testArray_merge()
    {
        $this->assertSame(
            array("a"=>"bleu", "b"=>"blanc", "c"=>"rouge"),
            Gb_Util::array_merge(array("a"=>"bleu", "b"=>"blanc"), array("c"=>"rouge"))
        );
    }


    /**
     * Tests Gb_Util::getProjectName()
     */
    public function testGetProjectName()
    {
        $this->assertEquals("testProject", Gb_Glue::getProjectName() );
    }


    /**
     * Tests Gb_Util::include_file()
     */
    public function testInclude_file()
    {
        try {
            Gb_Util::include_file("doesnotexist");
        } catch (Gb_Exception $e) {
            $e;
            return;  
        }
        $this->fail("Exception non attrapée");
    }


    /**
     * Tests Gb_Util::roundCeil()
     */
    public function testRoundCeil()
    {
    	$this->assertEquals((string)120000, (string)Gb_Util::roundCeil(120000  ,2));
    	$this->assertEquals((string)130000, (string)Gb_Util::roundCeil(120000.1,2));
    	$this->assertEquals((string)130000, (string)Gb_Util::roundCeil(129999.9,2));
    	$this->assertEquals((string)120000, (string)Gb_Util::roundCeil(120000  ,3));
    	$this->assertEquals((string)121000, (string)Gb_Util::roundCeil(120000.1,3));
    	$this->assertEquals((string)130000, (string)Gb_Util::roundCeil(129999.9,3));
    	
    	$this->assertEquals((string)1400, (string)Gb_Util::roundCeil(1400,2));
    	$this->assertEquals((string)1500, (string)Gb_Util::roundCeil(1400.01,2));
    	$this->assertEquals((string)1400, (string)Gb_Util::roundCeil(1400,3));
    	$this->assertEquals((string)1410, (string)Gb_Util::roundCeil(1400.01,3));
    	
    	$this->assertEquals((string)140, (string)Gb_Util::roundCeil(140,2));
    	$this->assertEquals((string)150, (string)Gb_Util::roundCeil(140.01,2));
    	$this->assertEquals((string)140, (string)Gb_Util::roundCeil(140,3));
    	$this->assertEquals((string)141, (string)Gb_Util::roundCeil(140.01,3));
    	
    	$this->assertEquals((string)14, (string)Gb_Util::roundCeil(14,2));
    	$this->assertEquals((string)15, (string)Gb_Util::roundCeil(14.01,2));
    	$this->assertEquals((string)14, (string)Gb_Util::roundCeil(14,3));
    	$this->assertEquals((string)14.1, (string)Gb_Util::roundCeil(14.01,3));
    	
    	$this->assertEquals((string)1.4, (string)Gb_Util::roundCeil(1.4,2));
    	$this->assertEquals((string)1.5, (string)Gb_Util::roundCeil(1.401,2));
    	$this->assertEquals((string)1.4, (string)Gb_Util::roundCeil(1.4,3));
    	$this->assertEquals((string)1.41, (string)Gb_Util::roundCeil(1.401,3));
    	
    	$this->assertEquals((string)0.14, (string)Gb_Util::roundCeil(.14,2));
    	$this->assertEquals((string)0.15, (string)Gb_Util::roundCeil(.1401,2));
    	$this->assertEquals((string)0.14, (string)Gb_Util::roundCeil(.14,3));
    	$this->assertEquals((string)0.141, (string)Gb_Util::roundCeil(.1401,3));
    	
    	$this->assertEquals((string)0.014, (string)Gb_Util::roundCeil(.014,2));
    	$this->assertEquals((string)0.015, (string)Gb_Util::roundCeil(.01401,2));
    	$this->assertEquals((string)0.014, (string)Gb_Util::roundCeil(.014,3));
    	$this->assertEquals((string)0.0141, (string)Gb_Util::roundCeil(.01401,3));
    	
    	$this->assertEquals((string)0.0014, (string)Gb_Util::roundCeil(.0014,2));
    	$this->assertEquals((string)0.0015, (string)Gb_Util::roundCeil(.001401,2));
    	$this->assertEquals((string)0.0014, (string)Gb_Util::roundCeil(.0014,3));
    	$this->assertEquals((string)0.00141, (string)Gb_Util::roundCeil(.001401,3));
    	
    	$this->assertEquals((string)0.00014, (string)Gb_Util::roundCeil(.00014,2));
    	$this->assertEquals((string)0.00015, (string)Gb_Util::roundCeil(.0001401,2));
    	$this->assertEquals((string)0.00014, (string)Gb_Util::roundCeil(.00014,3));
    	$this->assertEquals((string)0.000141, (string)Gb_Util::roundCeil(.0001401,3));
    }


    /**
     * Tests Gb_Util::startup()
     */
    public function testStartup()
    {
        // TODO Auto-generated Gb_UtilTest::testStartup()
        $this->markTestIncomplete("startup test not implemented");
        Gb_Util::startup(/* parameters */);
    }


    /**
     * Tests Gb_Util::url_debug()
     */
    public function testUrl_debug()
    {
        Gb_Util::$debug=false;
        $this->assertEquals("", Gb_Util::url_debug());
        $this->assertEquals("", Gb_Util::url_debug("&"));
        $this->assertEquals("", Gb_Util::url_debug("?"));
        Gb_Util::$debug=true;
        $this->assertEquals("&debug=1", Gb_Util::url_debug());
        $this->assertEquals("&debug=1", Gb_Util::url_debug("&"));
        $this->assertEquals("?debug=1", Gb_Util::url_debug("?"));
    }
}

