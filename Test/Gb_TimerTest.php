<?php
require_once 'Gb/Timer.php';
/**
 * Gb_Timer test case.
 */
class Gb_TimerTest extends PHPUnit_Framework_TestCase
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
        Gb_Log::$loglevel_showuser=Gb_Log::LOG_ALL;
        Gb_Log::$loglevel_file=Gb_Log::LOG_NONE;
        Gb_Log::$loglevel_footer=Gb_Log::LOG_NONE;
        $this->Gb_Timer=new Gb_Timer();
    }


    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated Gb_TimerTest::tearDown()
        $this->Gb_Timer=null;
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
     * Tests Gb_Timer->get()
     */
    public function testGet_Reset_Pause_Resume()
    {
        $this->Gb_Timer->pause();
        $old=$this->Gb_Timer->get();
        sleep(1);
        $this->assertEquals($old, $this->Gb_Timer->get());
        $this->Gb_Timer->resume();
        sleep(1);
        $this->assertGreaterThan($old, $this->Gb_Timer->get());
        $this->Gb_Timer->reset();
        $this->assertLessThan($old, $this->Gb_Timer->get());
        
    }


    /**
     * Tests Gb_Timer::get_nbInstance_peak()
     */
    public function testGet_nbInstance_peak_Get_nbInstance_Total_construct_destruct()
    {
        /*
        $oldpeak=$this->Gb_Timer->get_nbInstance_peak();
        $oldtotal=$this->Gb_Timer->get_nbInstance_total();
        $dumbtimer=new Gb_Timer();
        $this->assertEquals($oldpeak+1, $this->Gb_Timer->get_nbInstance_peak());
        $this->assertEquals($oldtotal+1, $this->Gb_Timer->get_nbInstance_total());
        $this->assertEquals($oldpeak+1, $dumbtimer->get_nbInstance_peak());
        $this->assertEquals($oldtotal+1, $dumbtimer->get_nbInstance_total());
        $dumbtimer=null;
        $this->assertEquals($oldpeak+1, $this->Gb_Timer->get_nbInstance_peak());
        $this->assertEquals($oldtotal+1, $this->Gb_Timer->get_nbInstance_total());
        $dumbtimer=new Gb_Timer();
        $this->assertEquals($oldpeak+1, $this->Gb_Timer->get_nbInstance_peak());
        $this->assertEquals($oldtotal+2, $this->Gb_Timer->get_nbInstance_total());
        $dumbtimer=null;
        $this->assertEquals($oldpeak+1, $this->Gb_Timer->get_nbInstance_peak());
        $this->assertEquals($oldtotal+2, $this->Gb_Timer->get_nbInstance_total());
    */
    }


    /**
     * Tests Gb_Timer->logTimer()
     */
    public function testLogTimer()
    {
        ob_start();
        $this->Gb_Timer->logTimer(Gb_Log::LOG_DEBUG, "test logTimer()");
        $ob=ob_get_clean();
        $this->assertRegExp("/^test logTimer\\(\\): [0-9\\.]{3,} s$/", $ob);
    }


    /**
     * Tests Gb_Timer->__toString()
     */
    public function test__toString()
    {
        $get=$this->Gb_Timer->__toString();
        $this->assertGreaterThan(0, $get);
        $this->assertLessThan(10, $get);
    }
}

