<?php
require_once 'Test/Gb_CacheTest.php';
require_once 'Test/Gb_DbTest.php';
require_once 'Test/Gb_StringTest.php';
require_once 'Test/Gb_TimerTest.php';
require_once 'Test/Gb_UtilTest.php';

/**
 * Static test suite.
 */
class gbphpdbSuite extends PHPUnit_Framework_TestSuite
{


    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName('gbphpdbSuite');
        $this->addTestSuite('Gb_CacheTest');
        $this->addTestSuite('Gb_DbTest');
        $this->addTestSuite('Gb_StringTest');
        $this->addTestSuite('Gb_TimerTest');
        $this->addTestSuite('Gb_UtilTest');
    }


    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}

