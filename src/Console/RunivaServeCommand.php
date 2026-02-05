<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva\Console;

use Glueful\Console\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'runiva:serve',
    description: 'Start the configured Runiva runtime (default: RoadRunner)'
)]
final class RunivaServeCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('config', InputArgument::OPTIONAL, 'Path to runtime config (e.g., rr.yaml)')
            ->addOption('runtime', null, InputOption::VALUE_OPTIONAL, 'Runtime engine (roadrunner|swoole|frankenphp)', (string) (config($this->getContext(), 'runiva.runtime') ?? 'roadrunner'))
            ->addOption('binary', null, InputOption::VALUE_OPTIONAL, 'Runtime binary', (string) (config($this->getContext(), 'runiva.binary') ?? 'rr'))
            ->addOption('check', null, InputOption::VALUE_NONE, 'Validate configuration and environment, then exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runtime = (string) $input->getOption('runtime');
        $binary = (string) $input->getOption('binary');
        $configArg = (string) ($input->getArgument('config') ?: (config($this->getContext(), 'runiva.config') ?? ''));

        // Optional config validation mode
        if ((bool) $input->getOption('check')) {
            $ok = $this->runChecks($runtime, $binary, $configArg);
            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $cmd = $this->buildCommand($runtime, $binary, $configArg);
        if ($cmd === null) {
            $this->error('Unsupported runtime: ' . $runtime);
            return self::FAILURE;
        }

        if ($configArg !== '' && !is_file($configArg)) {
            $this->warning('Config not found: ' . $configArg . ' (continuing if runtime does not require it)');
        }

        $this->info('Starting ' . $runtime . ': ' . $cmd);
        passthru($cmd, $exit);
        return is_int($exit) ? $exit : self::FAILURE;
    }

    private function buildCommand(string $runtime, string $binary, string $cfg): ?string
    {
        return match ($runtime) {
            'roadrunner' => $this->rrCommand($binary, $cfg),
            'swoole', 'openswoole' => $this->phpScriptCommand('vendor/glueful/runiva/bin/swoole-server.php'),
            'frankenphp' => $this->phpScriptCommand('vendor/glueful/runiva/bin/frankenphp-server.php'),
            default => null,
        };
    }

    private function rrCommand(string $binary, string $cfg): string
    {
        $args = $cfg !== '' ? ' -c ' . escapeshellarg($cfg) : '';
        return escapeshellcmd($binary) . ' serve' . $args;
    }

    private function phpScriptCommand(string $script): string
    {
        return 'php ' . escapeshellarg(base_path($this->getContext(), $script));
    }

    private function runChecks(string $runtime, string $binary, string $cfg): bool
    {
        $allOk = true;

        $this->info('Runiva environment check');
        $this->line('  runtime: ' . $runtime);

        switch ($runtime) {
            case 'roadrunner':
                if (!$this->binaryExists($binary)) {
                    $this->error('rr binary not found: ' . $binary . ' (set RUNIVA_BINARY or install rr)');
                    $allOk = false;
                } else {
                    $this->line('  rr binary: OK');
                }
                if ($cfg !== '' && !is_file($cfg)) {
                    $this->warning('rr config not found: ' . $cfg);
                } else {
                    $this->line('  rr config: OK');
                }
                // PSR-7 bridge classes
                $psr7Ok = class_exists('Nyholm\\Psr7\\Factory\\Psr17Factory')
                    && class_exists('Symfony\\Bridge\\PsrHttpMessage\\Factory\\PsrHttpFactory');
                if (!$psr7Ok) {
                    $this->warning('PSR-7 bridge not detected (nyholm/psr7, symfony/psr-http-message-bridge)');
                } else {
                    $this->line('  PSR-7 bridge: OK');
                }
                $rrHttpOk = class_exists('Spiral\\RoadRunner\\Http\\PSR7Worker');
                if (!$rrHttpOk) {
                    $this->warning('RoadRunner HTTP worker class not found (spiral/roadrunner-http)');
                } else {
                    $this->line('  roadrunner-http: OK');
                }
                break;

            case 'swoole':
            case 'openswoole':
                $extOk = extension_loaded('swoole') || extension_loaded('openswoole');
                if (!$extOk) {
                    $this->error('Swoole/OpenSwoole extension not loaded');
                    $allOk = false;
                } else {
                    $this->line('  Swoole/OpenSwoole extension: OK');
                }
                break;

            case 'frankenphp':
                $bin = getenv('FRANKENPHP_BINARY') ?: 'frankenphp';
                if (!$this->binaryExists($bin)) {
                    $this->error('FrankenPHP binary not found (set FRANKENPHP_BINARY or install frankenphp)');
                    $allOk = false;
                } else {
                    $this->line('  frankenphp binary: OK');
                }
                break;

            default:
                $this->error('Unsupported runtime: ' . $runtime);
                return false;
        }

        if ($allOk) {
            $this->success('Environment check passed');
        }
        return $allOk;
    }

    private function binaryExists(string $binary): bool
    {
        // Absolute path
        if ($binary !== '' && ($binary[0] === '/' || preg_match('/^[A-Za-z]:\\\\/', $binary) === 1)) {
            return is_file($binary) && is_executable($binary);
        }
        // Use shell to locate in PATH
        $cmd = (stripos(PHP_OS, 'WIN') === 0)
            ? 'where ' . escapeshellarg($binary)
            : 'command -v ' . escapeshellcmd($binary) . ' 2>/dev/null';
        $out = @shell_exec($cmd);
        return is_string($out) && trim($out) !== '';
    }
}
