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
  - `ext-swoole` or `ext-openswoole` PHP extension

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
- A stub launcher is provided at `vendor/glueful/runiva/bin/frankenphp-server.php`.
- Prefer the official FrankenPHP CLI/Caddy integration for production.



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
