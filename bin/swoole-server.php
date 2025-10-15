<?php

declare(strict_types=1);

if (!extension_loaded('swoole') && !extension_loaded('openswoole')) {
    fwrite(STDERR, "Swoole/OpenSwoole extension not installed.\n");
    exit(1);
}

echo "Runiva Swoole server stub. Implement request translation and startup here.\n";
exit(0);

