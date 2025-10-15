<?php

declare(strict_types=1);

use Glueful\Framework;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as SfRequest;

if (!extension_loaded('swoole') && !extension_loaded('openswoole')) {
    fwrite(STDERR, "Swoole/OpenSwoole extension not installed.\n");
    exit(1);
}

require __DIR__ . '/../../../vendor/autoload.php';

// Detect project root (vendor/<vendor>/<package>/bin -> project root)
$projectRoot = dirname(__DIR__, 4);
if (!is_file($projectRoot . '/composer.json')) {
    $projectRoot = getcwd() ?: $projectRoot;
}

// Boot Glueful
try {
    if (!class_exists(Framework::class)) {
        throw new RuntimeException('Glueful Framework not found. Ensure glueful/framework is installed.');
    }
    $app = Framework::create($projectRoot)->boot();
} catch (Throwable $e) {
    fwrite(STDERR, 'Failed to boot Glueful: ' . $e->getMessage() . "\n");
    exit(1);
}

// Determine host/port from config `runiva.address` (e.g., ":8080" or "127.0.0.1:8080")
$address = (string) (config('runiva.address') ?? ':8080');
[$host, $port] = (function (string $addr): array {
    $h = '0.0.0.0';
    $p = 8080;
    if (preg_match('/^(?<host>[^:]*):(?<port>\d+)$/', $addr, $m)) {
        $h = $m['host'] !== '' ? $m['host'] : '0.0.0.0';
        $p = (int) $m['port'];
    }
    return [$h, $p];
})($address);

// Resolve server class — support OpenSwoole (HTTP/Http) and Swoole
$openSwooleCandidates = ['OpenSwoole\\HTTP\\Server', 'OpenSwoole\\Http\\Server'];
$serverClass = null;
foreach ($openSwooleCandidates as $candidate) {
    if (class_exists($candidate)) {
        $serverClass = $candidate;
        break;
    }
}
if ($serverClass === null) {
    $serverClass = 'Swoole\\Http\\Server';
}

/** @var object $server */
$server = new $serverClass($host, $port);

$server->set([
    'worker_num' => (int) (config('runiva.workers') ?? 2),
]);

$server->on('request', function ($req, $res) use ($app) {
    try {
        $sfReq = swooleToSymfonyRequest($req);
        $sfRes = $app->handle($sfReq);

        // Status
        $res->status($sfRes->getStatusCode());

        // Headers
        foreach ($sfRes->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            // Swoole accepts string header values; join multi-values with comma
            $res->header($name, implode(', ', array_map('strval', (array) $values)));
        }

        // Cookies
        foreach ($sfRes->headers->getCookies() as $cookie) {
            $res->header('Set-Cookie', (string) $cookie, false);
        }

        // Body
        $content = $sfRes->getContent();
        $res->end($content === false ? '' : $content);

        $app->terminate($sfReq, $sfRes);
    } catch (Throwable $e) {
        $res->status(500);
        $res->end('Internal Server Error');
        error_log('[Runiva][Swoole] ' . $e->getMessage());
    }
});

echo sprintf("Runiva Swoole server listening on %s:%d\n", $host, $port);
$server->start();

/**
 * Convert Swoole/OpenSwoole Request to Symfony HttpFoundation Request.
 * @param mixed $req
 */
function swooleToSymfonyRequest($req): SfRequest
{
    $method = strtoupper($req->server['request_method'] ?? 'GET');
    $path = $req->server['request_uri'] ?? '/';
    $queryString = $req->server['query_string'] ?? '';
    $headers = (array) ($req->header ?? []);

    $scheme = (isset($headers['x-forwarded-proto']) && $headers['x-forwarded-proto'] === 'https')
        ? 'https'
        : 'http';
    $host = $headers['host'] ?? '127.0.0.1';
    $uri = $scheme . '://' . $host . $path . ($queryString !== '' ? ('?' . $queryString) : '');

    // Server params: map headers to HTTP_* and pass basic server values
    $server = [];
    foreach ($headers as $name => $value) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $server[$key] = $value;
    }
    $server['REQUEST_METHOD'] = $method;
    $server['REQUEST_URI'] = $path;
    $server['QUERY_STRING'] = $queryString;
    $server['SERVER_PROTOCOL'] = $req->server['server_protocol'] ?? '1.1';
    $server['REMOTE_ADDR'] = $req->server['remote_addr'] ?? '127.0.0.1';

    $cookies = (array) ($req->cookie ?? []);
    $get = (array) ($req->get ?? []);
    $post = (array) ($req->post ?? []);
    $files = isset($req->files) && is_array($req->files) ? mapSwooleFiles($req->files) : [];
    $content = $req->rawContent() ?: null;

    return SfRequest::create($uri, $method, $post ?: $get, $cookies, $files, $server, $content);
}

/**
 * Recursively convert Swoole/OpenSwoole files array to Symfony UploadedFile instances.
 * Uses test=true to bypass is_uploaded_file() since Swoole wrote the temp file.
 * @param array<string, mixed> $files
 * @return array<string, mixed>
 */
function mapSwooleFiles(array $files): array
{
    $map = function ($node) use (&$map) {
        if (is_array($node) && isset($node['tmp_name']) && array_key_exists('error', $node)) {
            $path = (string) ($node['tmp_name'] ?? '');
            $name = (string) ($node['name'] ?? 'file');
            $type = isset($node['type']) ? (string) $node['type'] : null;
            $error = (int) ($node['error'] ?? UPLOAD_ERR_NO_FILE);
            // Size is not required by UploadedFile; Symfony reads from filesystem when needed
            return new UploadedFile($path, $name, $type, $error, true);
        }
        if (is_array($node)) {
            $out = [];
            foreach ($node as $k => $v) {
                $out[$k] = $map($v);
            }
            return $out;
        }
        return $node;
    };
    return $map($files);
}
