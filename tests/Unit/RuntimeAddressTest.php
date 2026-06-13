<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva\Tests\Unit;

use Glueful\Extensions\Runiva\Support\RuntimeAddress;
use PHPUnit\Framework\TestCase;

final class RuntimeAddressTest extends TestCase
{
    public function testEmptyHostBindsToLoopbackByDefault(): void
    {
        $address = RuntimeAddress::parse(':8080');

        self::assertSame('127.0.0.1', $address->host);
        self::assertSame(8080, $address->port);
    }

    public function testExplicitHostIsPreserved(): void
    {
        $address = RuntimeAddress::parse('0.0.0.0:9090');

        self::assertSame('0.0.0.0', $address->host);
        self::assertSame(9090, $address->port);
    }

    public function testInvalidAddressFallsBackToLoopback(): void
    {
        $address = RuntimeAddress::parse('not-a-socket');

        self::assertSame('127.0.0.1', $address->host);
        self::assertSame(8080, $address->port);
    }
}
