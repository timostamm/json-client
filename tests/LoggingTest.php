<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 15.05.18
 * Time: 14:02
 */

namespace TS\Web\JsonClient;


use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TS\Web\JsonClient\HttpLogging\HttpPsrLogLogger;


class LoggingTest extends TestCase
{


    public function testNotAPsrLogLogger()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected psrLogger to implement Psr\Log\LoggerInterface');
        new HttpPsrLogLogger($this);
    }

    public function testPsrLogLogger()
    {
        $l = new HttpPsrLogLogger(new NullLogger());
        $this->assertNotNull($l);
    }

}