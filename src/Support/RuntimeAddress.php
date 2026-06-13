<?php

declare(strict_types=1);

namespace Glueful\Extensions\Runiva\Support;

final readonly class RuntimeAddress
{
    public function __construct(
        public string $host,
        public int $port,
    ) {
    }

    public static function parse(string $address): self
    {
        if (preg_match('/^(?<host>[^:]*):(?<port>\d+)$/', $address, $matches) !== 1) {
            return new self('127.0.0.1', 8080);
        }

        $host = $matches['host'] !== '' ? $matches['host'] : '127.0.0.1';
        $port = (int) $matches['port'];

        return new self($host, $port > 0 ? $port : 8080);
    }
}
