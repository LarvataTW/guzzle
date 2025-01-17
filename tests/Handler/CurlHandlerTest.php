<?php
namespace Larvatatw\Test\Handler;

use Larvatatw\Exception\ConnectException;
use Larvatatw\Handler\CurlHandler;
use Larvatatw\Psr7;
use Larvatatw\Psr7\Request;
use Larvatatw\Psr7\Response;
use Larvatatw\Tests\Server;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GuzzleHttp\Handler\CurlHandler
 */
class CurlHandlerTest extends TestCase
{
    protected function getHandler($options = [])
    {
        return new CurlHandler($options);
    }

    /**
     * @expectedException \GuzzleHttp\Exception\ConnectException
     * @expectedExceptionMessage cURL
     */
    public function testCreatesCurlErrors()
    {
        $handler = new CurlHandler();
        $request = new Request('GET', 'http://localhost:123');
        $handler($request, ['timeout' => 0.001, 'connect_timeout' => 0.001])->wait();
    }

    public function testReusesHandles()
    {
        Server::flush();
        $response = new response(200);
        Server::enqueue([$response, $response]);
        $a = new CurlHandler();
        $request = new Request('GET', Server::$url);
        self::assertInstanceOf('GuzzleHttp\Promise\FulfilledPromise', $a($request, []));
        self::assertInstanceOf('GuzzleHttp\Promise\FulfilledPromise', $a($request, []));
    }

    public function testDoesSleep()
    {
        $response = new response(200);
        Server::enqueue([$response]);
        $a = new CurlHandler();
        $request = new Request('GET', Server::$url);
        $s = \GuzzleHttp\_current_time();
        $a($request, ['delay' => 0.1])->wait();
        self::assertGreaterThan(0.0001, \GuzzleHttp\_current_time() - $s);
    }

    public function testCreatesCurlErrorsWithContext()
    {
        $handler = new CurlHandler();
        $request = new Request('GET', 'http://localhost:123');
        $called = false;
        $p = $handler($request, ['timeout' => 0.001, 'connect_timeout' => 0.001])
            ->otherwise(function (ConnectException $e) use (&$called) {
                $called = true;
                self::assertArrayHasKey('errno', $e->getHandlerContext());
            });
        $p->wait();
        self::assertTrue($called);
    }

    public function testUsesContentLengthWhenOverInMemorySize()
    {
        Server::flush();
        Server::enqueue([new Response()]);
        $stream = Psr7\stream_for(str_repeat('.', 1000000));
        $handler = new CurlHandler();
        $request = new Request(
            'PUT',
            Server::$url,
            ['Content-Length' => 1000000],
            $stream
        );
        $handler($request, [])->wait();
        $received = Server::received()[0];
        self::assertEquals(1000000, $received->getHeaderLine('Content-Length'));
        self::assertFalse($received->hasHeader('Transfer-Encoding'));
    }
}
