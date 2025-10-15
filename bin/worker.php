<?php

declare(strict_types=1);

use Spiral\RoadRunner\Worker;

require __DIR__ . '/../../../vendor/autoload.php';

$worker = Worker::create();

// Detect project root (vendor/<vendor>/<package>/bin -> project root)
$projectRoot = dirname(__DIR__, 4);
if (!is_file($projectRoot . '/composer.json')) {
    // Fallback: try current working directory
    $projectRoot = getcwd() ?: $projectRoot;
}

// Boot Glueful framework and application
try {
    if (!class_exists(\Glueful\Framework::class)) {
        throw new RuntimeException('Glueful Framework not found. Ensure glueful/framework is installed.');
    }
    $app = \Glueful\Framework::create($projectRoot)->boot();
} catch (Throwable $e) {
    $worker->error('Failed to boot Glueful: ' . $e->getMessage());
    exit(1);
}

// Prefer PSR-7 integration when available
$hasPsr7 = class_exists(Spiral\RoadRunner\Http\PSR7Worker::class)
    && class_exists(Nyholm\Psr7\Factory\Psr17Factory::class)
    && class_exists(Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory::class)
    && class_exists(Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory::class);

if ($hasPsr7) {
    $psr17 = new Nyholm\Psr7\Factory\Psr17Factory();
    $psrHttpFactory = new Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory($psr17, $psr17, $psr17, $psr17);
    $httpFoundationFactory = new Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();
    $psr7Worker = new Spiral\RoadRunner\Http\PSR7Worker($worker, $psr17, $psr17, $psr17);

    while (true) {
        try {
            $psrRequest = $psr7Worker->waitRequest();
            if ($psrRequest === null) {
                continue;
            }
            $sfRequest = $httpFoundationFactory->createRequest($psrRequest);
            $sfResponse = $app->handle($sfRequest);
            $psrResponse = $psrHttpFactory->createResponse($sfResponse);
            $psr7Worker->respond($psrResponse);
            $app->terminate($sfRequest, $sfResponse);
        } catch (Throwable $e) {
            $psr7Worker->respond(new Nyholm\Psr7\Response(500, ['Content-Type' => 'text/plain'], 'Internal Server Error'));
            $worker->error($e->getMessage());
        }
    }
}

// Fallback: basic payload loop (no HTTP). Instruct to install PSR-7 bridge deps.
$worker->error(
    'PSR-7 bridge not available. Install nyholm/psr7 and symfony/psr-http-message-bridge, '
    . 'and RoadRunner HTTP package. Falling back to payload loop.'
);

while ($payload = $worker->waitPayload()) {
    try {
        $worker->respond(new Spiral\RoadRunner\Payload('OK'));
    } catch (Throwable $e) {
        $worker->error($e->getMessage());
    }
}

