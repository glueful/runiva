<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Extensions\ServiceProvider;

final class RunivaServiceProvider extends ServiceProvider
{
    private static ?string $cachedVersion = null;

    /**
     * Read the extension version from composer.json (cached)
     */
    public static function composerVersion(): string
    {
        if (self::$cachedVersion === null) {
            $path = __DIR__ . '/../composer.json';
            $composer = json_decode(file_get_contents($path), true);
            self::$cachedVersion = $composer['version'] ?? '0.0.0';
        }

        return self::$cachedVersion;
    }

    public function register(ApplicationContext $context): void
    {
        // Merge default config from package; app overrides win
        $this->mergeConfig('runiva', require __DIR__ . '/../config/runiva.php');
    }

    public function boot(ApplicationContext $context): void
    {
        // Auto-discover CLI commands from Console/ directory
        // Commands must have #[AsCommand] attribute to be discovered
        $this->discoverCommands(
            'Glueful\\Extensions\\Runiva\\Console',
            __DIR__ . '/Console'
        );

        // Register extension metadata for CLI and diagnostics
        try {
            $this->app->get(\Glueful\Extensions\ExtensionManager::class)->registerMeta(self::class, [
                'slug' => 'runiva',
                'name' => 'Runiva',
                'version' => self::composerVersion(),
                'description' => 'Server runtime integration for Glueful',
            ]);
        } catch (\Throwable $e) {
            error_log('[Runiva] Failed to register extension metadata: ' . $e->getMessage());
        }
    }
}
