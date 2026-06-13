<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SwooleServerConfigTest extends TestCase
{
    public function testPackagedSwooleServerDisablesCoroutineRequestConcurrency(): void
    {
        $source = file_get_contents(__DIR__ . '/../../bin/swoole-server.php');

        self::assertIsString($source);
        self::assertStringContainsString("'enable_coroutine' => false", $source);
    }
}
