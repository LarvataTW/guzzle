<?php
namespace Larvatatw\Test\Handler;

use Larvatatw\Handler\EasyHandle;
use Larvatatw\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GuzzleHttp\Handler\EasyHandle
 */
class EasyHandleTest extends TestCase
{
    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage The EasyHandle has been released
     */
    public function testEnsuresHandleExists()
    {
        $easy = new EasyHandle;
        unset($easy->handle);
        $easy->handle;
    }
}
