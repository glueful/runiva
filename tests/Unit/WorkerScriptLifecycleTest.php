<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WorkerScriptLifecycleTest extends TestCase
{
    public function testRoadRunnerWorkerTerminatesRequestsInFinallyBlock(): void
    {
        $source = file_get_contents(__DIR__ . '/../../bin/worker.php');

        self::assertIsString($source);
        self::assertMatchesRegularExpression('/finally\s*\{[^}]*\$app->terminate/s', $source);
    }

    public function testSwooleWorkerTerminatesRequestsInFinallyBlock(): void
    {
        $source = file_get_contents(__DIR__ . '/../../bin/swoole-server.php');

        self::assertIsString($source);
        self::assertMatchesRegularExpression('/finally\s*\{[^}]*\$app->terminate/s', $source);
    }
}
