# Changelog

All notable changes to the Runiva (Server Runtime) extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- OpenSwoole coroutine support improvements
- FrankenPHP worker mode enhancements
- Health check integration with runtime-specific metrics

## [0.8.1] - 2026-02-06

### Changed
- **Version Management**: Version is now read from `composer.json` at runtime via `RunivaServiceProvider::composerVersion()`.
  - `registerMeta()` in `boot()` now uses `self::composerVersion()` instead of a hardcoded string.
  - Future releases only require updating `composer.json` and `CHANGELOG.md`.

### Notes
- No breaking changes. Internal refactor only.

## [0.8.0] - 2026-02-05

### Changed
- **Framework Compatibility**: Updated minimum framework requirement to Glueful 1.28.0
  - Compatible with route caching infrastructure (Bellatrix release)
  - Benefits from improved route cache signature-based invalidation
- **composer.json**: Updated `extra.glueful.requires.glueful` to `>=1.28.0`

### Framework Features Now Available
- **Route Caching**: Long-running servers benefit from pre-compiled route caches
- **Closure Detection**: Framework warns about non-cacheable routes
- **ResourceController Refactoring**: Framework controllers use RESTful naming conventions

### Notes
- This release ensures compatibility with Glueful Framework 1.28.0's route caching improvements
- No code changes required - runtime adapters work seamlessly with cached routes
- Run `composer update` after upgrading

## [0.7.0] - 2026-01-31

### Changed
- **Framework Compatibility**: Updated minimum framework requirement to Glueful 1.22.0
  - Compatible with the new `ApplicationContext` dependency injection pattern
  - No code changes required in extension - framework handles context propagation
- **composer.json**: Updated `extra.glueful.requires.glueful` to `>=1.22.0`

### Notes
- This release ensures compatibility with Glueful Framework 1.22.0's context-based dependency injection
- All existing functionality remains unchanged
- Run `composer update` after upgrading

## [0.6.0] - 2026-01-17

### Breaking Changes
- **PHP 8.3 Required**: Minimum PHP version raised from 8.2 to 8.3.
- **Glueful 1.9.0 Required**: Minimum framework version raised to 1.9.0.

### Changed
- Updated `composer.json` PHP requirement to `^8.3`.
- Updated `extra.glueful.requires.glueful` to `>=1.9.0`.

### Notes
- Ensure your environment runs PHP 8.3 or higher before upgrading.
- Run `composer update` after upgrading.

## [0.5.0] - 2025-12-20

### Added
- **FrankenPHP Support**: Full integration with FrankenPHP runtime.
  - Automatic Caddyfile generation for development.
  - Production Caddyfile examples with TLS, security headers, and static asset handling.
  - Environment variable passthrough (`APP_ENV`, `APP_DEBUG`).

### Changed
- Improved documentation with detailed FrankenPHP setup instructions.
- Added production deployment guidelines for all runtimes.

## [0.4.0] - 2025-11-15

### Added
- **Swoole/OpenSwoole Support**: Server integration for Swoole and OpenSwoole runtimes.
  - Graceful shutdown handling via `shutdown` and `workerStop` events.
  - Hot-reload support via `SIGUSR1` signal.

### Enhanced
- Unified `runiva:serve` command now supports `--runtime=swoole` option.
- Environment-driven configuration for all runtimes.

## [0.3.0] - 2025-10-01

### Added
- **Hot-Reload Support**: RoadRunner `watch` plugin configuration.
  - Auto-reload workers on PHP file changes during development.
  - Configurable patterns and ignore rules.

### Changed
- Improved worker lifecycle management with proper signal handling.
- Enhanced PSR-7 bridge detection and error messaging.

## [0.2.0] - 2025-09-01

### Added
- **PSR-7 Bridge**: Automatic detection and integration.
  - Supports `nyholm/psr7` + `symfony/psr-http-message-bridge`.
  - Seamless conversion between PSR-7 and Symfony HttpFoundation.

### Enhanced
- Improved error handling in worker loop.
- Better logging for request/response conversion failures.

## [0.1.0] - 2025-08-01

### Added
- **Initial Release**: Server runtime extension scaffold.
- **RoadRunner Support**: Full integration with RoadRunner HTTP worker.
  - PSR-7 request handling via `spiral/roadrunner-http`.
  - Default `rr.yaml` configuration included.
  - Graceful shutdown on `SIGTERM/SIGINT/SIGQUIT`.
- **Console Command**: `runiva:serve` for starting configured runtime.
  - `--check` flag for configuration validation without starting.
  - `--runtime` flag to override configured runtime.
- **Configuration**: Environment-driven via `.env` and `config/runiva.php`.
  - `RUNIVA_RUNTIME`, `RUNIVA_BINARY`, `RUNIVA_CONFIG`, `RUNIVA_WORKERS`, `RUNIVA_ADDRESS`.

### Infrastructure
- Modern extension architecture with `RunivaServiceProvider`.
- PSR-4 autoloading under `Glueful\Extensions\Runiva`.
- Composer-based discovery and installation.

---

## Runtime Comparison

| Runtime | Use Case | Hot Reload | Dependencies |
|---------|----------|------------|--------------|
| RoadRunner | High-performance HTTP | `watch` plugin | `spiral/roadrunner-http` |
| Swoole | Coroutines, WebSockets | `SIGUSR1` signal | `ext-swoole` |
| FrankenPHP | Modern PHP + Caddy | Process restart | `frankenphp` binary |

## Quick Start

```bash
# Install extension
composer require glueful/runiva

# RoadRunner dependencies (recommended)
composer require spiral/roadrunner spiral/roadrunner-http nyholm/psr7 symfony/psr-http-message-bridge

# Start server
php glueful runiva:serve

# Validate configuration
php glueful runiva:serve --check
```

---

**Full Changelog**: https://github.com/glueful/runiva/commits/main
