<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva\Console;

use Glueful\Console\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RunivaServeCommand extends BaseCommand
{
    protected static $defaultName = 'runiva:serve';

    protected function configure(): void
    {
        $this
            ->setDescription('Start the configured Runiva runtime (default: RoadRunner)')
            ->addArgument('config', InputArgument::OPTIONAL, 'Path to runtime config (e.g., rr.yaml)')
            ->addOption('runtime', null, InputOption::VALUE_OPTIONAL, 'Runtime engine (roadrunner|swoole|frankenphp)', (string) (config('runiva.runtime') ?? 'roadrunner'))
            ->addOption('binary', null, InputOption::VALUE_OPTIONAL, 'Runtime binary', (string) (config('runiva.binary') ?? 'rr'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runtime = (string) $input->getOption('runtime');
        $binary = (string) $input->getOption('binary');
        $configArg = (string) ($input->getArgument('config') ?: (config('runiva.config') ?? ''));

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
        return 'php ' . escapeshellarg(base_path($script));
    }
}
