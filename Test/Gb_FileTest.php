<?php
require_once 'Gb/File.php';
require_once 'Gb/Util.php';
require_once 'PHPUnit/Framework/TestCase.php';
/**
 * Gb_File test case.
 */
class Gb_FileTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Gb_File
     */
    private $Gb_File;

    private $sourcefsname;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        $tmpdir=Gb_Util::sys_get_temp_dir();
        
        $this->sourcefsname=$tmpdir.DIRECTORY_SEPARATOR."testabd1234.1_2-1.tmp";
        file_put_contents($this->sourcefsname, "TEST");

        //$this->Gb_File=new Gb_File("/var/lib/php5/files/test");
        $this->Gb_File=new Gb_File("d:\\win\\temp\\test");
    }


    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated Gb_FileTest::tearDown()
        $this->Gb_File=null;
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
     * Tests Gb_File->__construct()
     */
    public function test__construct()
    {
        // insère une ligne avec une clé primaire déjà existante
        $ok=false;
        try {
            $this->Gb_File=new Gb_File("/var/lib/php5/files/nonexistent");
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("not exception");
        }
        
    }


    /**
     * Tests Gb_File->getFsName()
     */
    public function testGetFsName()
    {
        // TODO Auto-generated Gb_FileTest->testGetFsName()
        $this->markTestIncomplete(
            "getFsName test not implemented");
        $this->Gb_File->getFsName(/* parameters */);
    }


    /**
     * Tests Gb_File->purgeTempDir()
     */
    public function testPurgeTempDir()
    {
        // TODO Auto-generated Gb_FileTest->testPurgeTempDir()
        $this->markTestIncomplete(
            "purgeTempDir test not implemented");
        $this->Gb_File->purgeTempDir(/* parameters */);
    }


    /**
     * Tests Gb_File->store()
     */
    public function testStore()
    {
        // TODO Auto-generated Gb_FileTest->testStore()
        $this->Gb_File->store($this->sourcefsname, "rzeré--_32.abc.txt", "abc/def", "prefix");
    }


    /**
     * Tests Gb_File->storeUploadedTemporary()
     */
    public function testStoreUploadedTemporary()
    {
        // TODO Auto-generated Gb_FileTest->testStoreUploadedTemporary()
        $this->markTestIncomplete(
            "storeUploadedTemporary test not implemented");
        $this->Gb_File->storeUploadedTemporary(/* parameters */);
    }
}

