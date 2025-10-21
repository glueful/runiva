<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva;

use Glueful\Extensions\ServiceProvider;

final class RunivaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config from package; app overrides win
        $this->mergeConfig('runiva', require __DIR__ . '/../config/runiva.php');
    }

    public function boot(): void
    {
        $this->commands([
            Console\RunivaServeCommand::class,
        ]);

        // Register extension metadata for CLI and diagnostics
        try {
            $this->app->get(\Glueful\Extensions\ExtensionManager::class)->registerMeta(self::class, [
                'slug' => 'runiva',
                'name' => 'Runiva',
                'version' => '0.5.1',
                'description' => 'Server runtime integration for Glueful',
            ]);
        } catch (\Throwable $e) {
            error_log('[Runiva] Failed to register extension metadata: ' . $e->getMessage());
        }
    }
}
