<?php
/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */

namespace Gb\Tests;


use \PHPUnit_Framework_TestCase;

use \Gb;

/**
 * AbstractGbTest
 *
 * Base test class for PHP Unit testing
 *
 * @uses PHPUnit_Framework_TestCase
 * @abstract
 * @package Klein\Tests
 */
abstract class AbstractGbTest extends PHPUnit_Framework_TestCase
{

    /**
     * Setup our test
     * (runs before each test)
     *
     * @access protected
     * @return void
     */
    protected function setUp() {
    }

}
