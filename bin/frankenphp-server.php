<?php

declare(strict_types=1);

// Minimal FrankenPHP launcher for Glueful apps.
// Requires the `frankenphp` binary to be installed and available in PATH
// or provided via the FRANKENPHP_BINARY environment variable.

// Autoload (in case users run this directly)
require __DIR__ . '/../../../vendor/autoload.php';

// Detect project root
$projectRoot = dirname(__DIR__, 4);
if (!is_file($projectRoot . '/composer.json')) {
    $projectRoot = getcwd() ?: $projectRoot;
}

// Resolve public directory
$publicDir = $projectRoot . '/public';
if (!is_dir($publicDir)) {
    // Fallback to project root; FrankenPHP will still route to index.php if present
    $publicDir = $projectRoot;
}

// Resolve listen address from config/env (e.g., ":8080" or "127.0.0.1:8080")
$address = (string) (function (): string {
    if (function_exists('config')) {
        $addr = config('runiva.address');
        if (is_string($addr) && $addr !== '') {
            return $addr;
        }
    }
    $env = getenv('RUNIVA_ADDRESS');
    return is_string($env) && $env !== '' ? $env : ':8080';
})();

// Find FrankenPHP binary
$bin = getenv('FRANKENPHP_BINARY') ?: 'frankenphp';

// Verify binary availability
$which = trim((string) shell_exec('command -v ' . escapeshellcmd($bin) . ' 2>/dev/null'));
if ($which === '') {
    fwrite(STDERR, "FrankenPHP binary not found. Install FrankenPHP or set FRANKENPHP_BINARY to its path.\n");
    exit(1);
}

// Ensure APP_ENV is propagated to the spawned server process
$appEnv = (function () {
    if (function_exists('env')) {
        $val = env('APP_ENV', null);
        if (is_string($val) && $val !== '') {
            return $val;
        }
    }
    $val = getenv('APP_ENV');
    return is_string($val) && $val !== '' ? $val : 'development';
})();
putenv('APP_ENV=' . $appEnv);
$_ENV['APP_ENV'] = $appEnv;

// Build a minimal temporary Caddyfile for FrankenPHP
$caddyfile = <<<CADDY
{
    order php_server before file_server
}

{$address} {
    root * {$publicDir}
    php_server
    file_server
}
CADDY;

$tmpFile = tempnam(sys_get_temp_dir(), 'runiva_frankenphp_');
if ($tmpFile === false) {
    fwrite(STDERR, "Failed to create temporary Caddyfile.\n");
    exit(1);
}
file_put_contents($tmpFile, $caddyfile);

$cmd = escapeshellcmd($bin) . ' run --config ' . escapeshellarg($tmpFile);
fwrite(STDOUT, "Starting FrankenPHP with config: {$tmpFile}\n");
fwrite(STDOUT, "Command: {$cmd}\n");

// Execute
passthru($cmd, $exit);

// Cleanup temp file (best effort)
@unlink($tmpFile);

exit(is_int($exit) ? $exit : 1);
