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
    }
}
