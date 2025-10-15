<?php

return [
    'runtime' => env('RUNIVA_RUNTIME', 'roadrunner'),
    'binary' => env('RUNIVA_BINARY', 'rr'),
    'config' => env('RUNIVA_CONFIG', __DIR__ . '/../rr.yaml'),
    'workers' => env('RUNIVA_WORKERS', 2),
    'address' => env('RUNIVA_ADDRESS', ':8080'),
];

