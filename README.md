# Runiva (Server Runtime) for Glueful

## Overview

Runiva integrates alternative server runtimes with the Glueful Framework — RoadRunner (default), (Open)Swoole, and FrankenPHP. It provides a unified command, environment‑driven configuration, and a PSR‑7 bridge to route HTTP requests through Glueful’s Application and Router.

## Features

- ✅ RoadRunner worker and config (rr.yaml)
- ✅ PSR‑7 bridge auto‑detection (Nyholm + Symfony bridge)
- ✅ Single console entrypoint: `runiva:serve`
- ✅ Config‑driven via `.env` + merged config
- ✅ Composer discovery via `extra.glueful.provider`
- ⚙️ Stubs for Swoole and FrankenPHP server scripts

## Requirements

- PHP 8.2+
- Glueful Framework ^1.6.2
- Optional (RoadRunner mode):
  - `spiral/roadrunner` (binary management + base worker)
  - `spiral/roadrunner-http` (PSR‑7 HTTP worker)
  - `nyholm/psr7`, `symfony/psr-http-message-bridge` (PSR‑7 <-> HttpFoundation)
  
Optional (Swoole/OpenSwoole mode):
- PHP extensions: `ext-swoole` or `ext-openswoole` (installed via PECL/package manager)

## Installation

```bash
composer require glueful/runiva
```

RoadRunner (recommended for HTTP)

```bash
composer require spiral/roadrunner spiral/roadrunner-http nyholm/psr7 symfony/psr-http-message-bridge
```

## Verify Installation

```bash
php glueful extensions:list
php glueful extensions:info Runiva
php glueful extensions:why Glueful\\Extensions\\Runiva\\RunivaServiceProvider
```

## Getting Started

Configuration via `.env` (all optional; defaults provided):

- `RUNIVA_RUNTIME=roadrunner|swoole|frankenphp`
- `RUNIVA_BINARY=rr` (RoadRunner binary or absolute path)
- `RUNIVA_CONFIG=vendor/glueful/runiva/rr.yaml`
- `RUNIVA_WORKERS=2`
- `RUNIVA_ADDRESS=:8080`

Runiva merges defaults from `config/runiva.php` within the package. App‑level `.env` values override these. You can also create `config/runiva.php` in your app to hard‑set values.

## Usage

Start the configured runtime:

```bash
php glueful runiva:serve
```

Force a specific runtime:

```bash
php glueful runiva:serve --runtime=roadrunner
php glueful runiva:serve --runtime=swoole
```

RoadRunner uses the included `rr.yaml` by default:

```yaml
server:
  command: "php vendor/glueful/runiva/bin/worker.php"
http:
  address: ":8080"
  pool:
    num_workers: 2
```

### RoadRunner + PSR‑7 Bridge

With `spiral/roadrunner-http`, `nyholm/psr7`, and `symfony/psr-http-message-bridge` installed, the worker will:
- Receive PSR‑7 requests from RoadRunner
- Convert to Symfony HttpFoundation requests
- Dispatch through Glueful Application/Router
- Convert Symfony responses back to PSR‑7 and respond

### Swoole / FrankenPHP

Swoole/OpenSwoole:
- Install the extension: `ext-swoole` or `ext-openswoole` must be enabled in PHP.
- Start the server:

```bash
php glueful runiva:serve --runtime=swoole
```

FrankenPHP:
- Ensure the `frankenphp` binary is installed and on PATH, or set `FRANKENPHP_BINARY` to its full path.
- Start the server:

```bash
php glueful runiva:serve --runtime=frankenphp
```

Notes:
- The launcher generates a minimal Caddyfile and runs `frankenphp run --config <tempfile>`.
- It sets `APP_ENV` based on your environment; adjust `RUNIVA_ADDRESS` to change the listen address (default `:8080`).
- For production, prefer a full Caddy configuration with TLS, timeouts, and headers tuned for your deployment.

#### Production Caddyfile (example)

```caddyfile
{
  # Global server options
  servers {
    timeouts {
      read_header 10s
      read_body   30s
      write       30s
      idle        2m
    }
    trusted_proxies private_ranges
  }
}

example.com {
  # ACME/Let’s Encrypt or email for zero‑config certificates
  tls you@example.com

  # Serve static assets efficiently
  encode zstd gzip

  # Path to your app’s public directory
  root * /var/www/public

  # Security headers (adjust CSP for your frontend needs)
  header {
    Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    X-Frame-Options "SAMEORIGIN"
    X-Content-Type-Options "nosniff"
    Referrer-Policy "no-referrer"
    X-XSS-Protection "0"
    Content-Security-Policy "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'"
  }

  # Long‑cache immutable assets
  @static {
    file
    path *.css *.js *.png *.jpg *.jpeg *.svg *.ico *.woff *.woff2 *.ttf *.map
  }
  handle @static {
    header Cache-Control "public, max-age=31536000, immutable"
    file_server
  }

  # PHP application served by FrankenPHP
  handle {
    php_server {
      # Optional tuning
      # worker 4
      env APP_ENV=production
      env APP_DEBUG=0
    }
  }

  # Structured access logs
  log {
    output stdout
    format json
  }
}
```

Adjust `example.com`, `root`, and header policies to your needs. For multi‑app setups, define multiple site blocks or use `handle_path` to mount sub‑apps.



## Troubleshooting

- Changes to `.env` and configs require a RoadRunner restart (hot workers persist state).
- If the worker logs that PSR‑7 bridge is unavailable, install:
  - `spiral/roadrunner spiral/roadrunner-http nyholm/psr7 symfony/psr-http-message-bridge`
- Ensure `rr` is installed and on PATH, or set `RUNIVA_BINARY` to an absolute path.

## Versioning

- Pre‑1.0: active development (0.2.x)
- Tag releases via git (e.g., `v0.2.0`). `extra.glueful.version` in composer.json mirrors the release for UI purposes.

---

Runiva is designed to be minimal and composable. Start with RoadRunner and PSR‑7 for full HTTP performance, then layer in Swoole or FrankenPHP as needed.
