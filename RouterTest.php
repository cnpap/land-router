<?php

namespace LandRouter;

use GuzzleHttp\Psr7\ServerRequest;
use Land15\Handle;
use Land15\Test\Handle as RequestHandle;
use Land15\Test\Process1;
use Land15\Test\Process2;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    /**
     * 使用示例 1
     */
    function testAllMethod()
    {
        $router = new Router();
        $router->get('some/path', RequestHandle::class);
        $router->post('/some/path', RequestHandle::class);
        $router->put('some/path/', RequestHandle::class);
        $router->delete('/some/path/', RequestHandle::class);
        $router->group([
            'middleware' => [
                Process1::class,
                Process2::class
            ],
            'prefix' => 'prefix'
        ], function (Router $router) {
            $router->get('/some/path/', RequestHandle::class);
            $router->post('some/path/', RequestHandle::class);
            $router->put('/some/path', RequestHandle::class);
            $router->delete('some/path', RequestHandle::class);
        });
        $expected = json_encode([
            'process1' => 'pass',
            'process2' => 'pass'
        ]);
        $this->assertEquals("[]", $router->handle(new ServerRequest('GET', '/some/path'))->getBody()->getContents());
        $this->assertEquals("[]", $router->handle(new ServerRequest('POST', '/some/path'))->getBody()->getContents());
        $this->assertEquals("[]", $router->handle(new ServerRequest('PUT', '/some/path'))->getBody()->getContents());
        $this->assertEquals("[]", $router->handle(new ServerRequest('DELETE', '/some/path'))->getBody()->getContents());
        $this->assertEquals($expected, $router->handle(new ServerRequest('GET', '/prefix/some/path'))->getBody()->getContents());
        $this->assertEquals($expected, $router->handle(new ServerRequest('POST', '/prefix/some/path'))->getBody()->getContents());
        $this->assertEquals($expected, $router->handle(new ServerRequest('PUT', '/prefix/some/path'))->getBody()->getContents());
        $this->assertEquals($expected, $router->handle(new ServerRequest('DELETE', '/prefix/some/path'))->getBody()->getContents());
    }

    function testLinkProcess()
    {
        $router = new Router();
        $router->get('/', RequestHandle::class);
        $handle = new Handle([
            Process1::class,
            $router
        ]);
        $expected = json_encode(['process1' => 'pass']);
        $this->assertEquals($expected, $handle->handle(new ServerRequest('GET', '/'))->getBody()->getContents());
    }
}