<?php
namespace Gb\Tests;

use \Gb;

/**
 * ModelTest
 *
 * @uses AbstractKleinTest
 * @package Klein\Tests
 */
class RequestTest extends AbstractGbTest
{

    public function testConstructorAndGetters() {
        // Test data
        $params_get  = array('get');
        $params_post = array('post');
        $cookies     = array('cookies');
        $server      = array('server');
        $files       = array('files');
        $body        = 'body';

        /*
            // Make sure our data's the same
            $this->assertSame($params_get, $request->paramsGet()->all());
            $this->assertSame($params_post, $request->paramsPost()->all());
            $this->assertSame($cookies, $request->cookies()->all());
            $this->assertSame($server, $request->server()->all());
            $this->assertSame($files, $request->files()->all());
            $this->assertSame($body, $request->body());
            $this->assertSame($params, $request->params());
            $this->assertSame($params['num'], $request->param('num'));
            $this->assertSame(null, $request->param('thisdoesntexist'));
            // Test Exists
            $this->assertTrue(isset($request->per_page));
            $this->assertNull($request->param('test'));
            $this->assertTrue($request->isSecure());
            $this->assertSame($ip, $request->ip());
            $this->assertSame($user_agent, $request->userAgent());
            $this->assertEmpty($request->body());
            // Make sure the ID's aren't null
            $this->assertNotNull($request_one->id());
            $this->assertNotNull($request_two->id());
            $this->assertContains($cookies[0], $request->params());
            $this->assertContains($server[0], $request->server()->all());
        */
    }
}
