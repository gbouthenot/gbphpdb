<?php
require_once 'Gb/Cache.php';

Gb_Cache::$cacheDir=".";

/**
 * CacheableObject test case.
 */
class Gb_CacheTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Gb_Cache
     */
    private $CacheableObject;


    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->CacheableObject=new Gb_Cache("test");
        $this->CacheableObject->testProperty="testValue";
    }


    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->CacheableObject=null;
        parent::tearDown();
    }


    /**
     * Constructs the test case.
     */
    public function __construct()
    {
    }


    /**
     * Tests CacheableObject->expire()
     */
    public function testExpire()
    {
        // vérifie que la propriété est mise, expire, vérifie que la prop est toujours là.
        $this->assertEquals($this->CacheableObject->testProperty, "testValue");
        $this->CacheableObject->expire();
        $this->assertEquals($this->CacheableObject->testProperty, "testValue");

        // détruit l'objet, le recrée et vérifie que la prop n'est plus là
        unset($this->CacheableObject);
        $this->assertFalse(isset($this->CacheableObject));
        $this->CacheableObject=new Gb_Cache("test");
        $this->assertNull($this->CacheableObject->testProperty);
    }


    /**
     * Tests CacheableObject->__construct() // __destruct()
     */
    public function test__construct() // __destruct()
    {
        $test2=new Gb_Cache("test2");
        $test2->test2Property="test2Value";
        $this->assertEquals($test2->test2Property, "test2Value");
        
        // détruit l'objet, le recrée et vérifie que les propriétés sont toujours là
        unset($this->CacheableObject);
        $this->assertFalse(isset($this->CacheableObject));
        $this->CacheableObject=new Gb_Cache("test");
        $this->assertEquals($this->CacheableObject->testProperty, "testValue");

        // test fichier
        file_put_contents("testfile", "");
        sleep(1);
        $this->assertFileExists("testfile");
        $testFile=new Gb_Cache("testFile", "testfile");
        $testFile->testFileProperty="testFileValue";
        $this->assertTrue(isset($testFile->testFileProperty));
        $this->assertEquals($testFile->testFileProperty, "testFileValue");
        unset($testFile);

        // recharge l'objet, il doit toujours être renseigné
        $testFile=new Gb_Cache("testFile", "testfile");
        $this->assertTrue(isset($testFile->testFileProperty));
        $this->assertEquals($testFile->testFileProperty, "testFileValue");
        unset($testFile);
        
        // met à jour le fichier, l'objet ne doit plus être rensigné
        file_put_contents("testfile", "");
        $testFile=new Gb_Cache("testFile", "testfile");
        $this->assertFalse(isset($testFile->testFileProperty));
    }


    /**
     * Tests CacheableObject->__get() / __set()
     */
    public function test__get()
    {
        $this->assertEquals($this->CacheableObject->testProperty, "testValue");
        $this->assertNull(  $this->CacheableObject->testDontExist);
        $this->assertNull(  $this->CacheableObject->testNewProperty);
        $this->CacheableObject->testNewProperty="testNewValue";
        $this->assertEquals($this->CacheableObject->testNewProperty, "testNewValue");
    }


    /**
     * Tests CacheableObject->__isset()
     */
    public function test__isset()
    {
        $this->assertFalse(  isset($this->CacheableObject->testDontExist) );
        $this->assertTrue( isset($this->CacheableObject->testProperty) );
    }

    
    /**
     * Tests CacheableObject->__empty()
     */
    public function test__empty()
    {
        $this->assertTrue(  empty($this->CacheableObject->testDontExist) );
        $this->assertFalse( empty($this->CacheableObject->testProperty) );
    }
    

    /**
     * Tests CacheableObject->__unset()
     */
    public function test__unset()
    {
        $this->assertNotNull(  $this->CacheableObject->testProperty);
        unset($this->CacheableObject->testProperty);
        $this->assertNull(     $this->CacheableObject->testProperty);
    }
    
    public function test_class()
    {
        $tc=new tstclass();
        $this->assertEquals("private public privatestatic publicstatic", $tc->getall());
        
        $tccache=new Gb_Cache('testtstclass', 30);
        if (empty($tccache->value)) {
            $tccache->value=$tc;
        }

        $this->assertEquals("private public privatestatic publicstatic", $tccache->value->getall());
        unset($tccache);
        unset($tc);
        
        $tccache2=new Gb_Cache('testtstclass', 30);
        $this->assertEquals("private public privatestatic publicstatic", $tccache2->value->getall());
        
    }
}


class tstclass
{
    private $prv;
    public  $pub;
    private static $prvstat;
    public  static $pubstat;
    
    function __construct()
    {
        $this->prv="private";
        $this->pub="public";
        self::$prvstat="privatestatic";
        self::$pubstat="publicstatic";
    }
    
    function getall()
    {
        return $this->prv." ".$this->pub." ".self::$prvstat." ".self::$pubstat;
    }
}


