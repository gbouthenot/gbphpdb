<?php

//set_include_path(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..").PATH_SEPARATOR.realpath(dirname(__FILE__)).PATH_SEPARATOR.get_include_path());

require_once 'Gb_CacheTest.php';
require_once 'Gb_DbTest.php';
require_once 'Gb_StringTest.php';
require_once 'Gb_TimerTest.php';
require_once 'Gb_UtilTest.php';

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
        chdir("tmp");
        return new self();
    }
}

