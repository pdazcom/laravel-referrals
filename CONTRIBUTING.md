# Contributing to Laravel Referrals

Thank you for considering a contribution! This guide covers everything you need to get the project running locally and submit a pull request.

## Prerequisites

| Requirement | Version |
|---|---|
| PHP for package development | 8.3 or 8.4 |
| PHP package support target | 8.2, 8.3, or 8.4 |
| Composer | 2.x |
| SQLite extension | `pdo_sqlite` |

No database server is required. Tests run against SQLite in-memory through Orchestra Testbench.

Why PHP 8.3+ for local development: this repository currently locks PHPUnit `12.x`, which requires PHP 8.3 or newer even though the package itself still supports PHP 8.2 in consuming Laravel applications.

## Local Setup

```bash
git clone https://github.com/pdazcom/laravel-referrals.git
cd laravel-referrals
composer install
```

## Running Tests

If your default `php` on `PATH` is already 8.3+, use:

```bash
composer test
```

If your default CLI PHP is older, call PHPUnit with an explicit 8.3+ binary instead:

```bash
/opt/homebrew/bin/php vendor/bin/phpunit --configuration phpunit.xml
```

At the time of writing, the suite contains 79 tests / 181 assertions. The test suite uses:

- **Orchestra Testbench** to bootstrap a minimal Laravel app
- **SQLite in-memory** — no external database needed
- **RefreshDatabase** — each test starts with a clean schema

### PHP version matrix

The package targets Laravel `^9.52.18|^10|^11|^12|^13` and PHP `^8.2`. CI covers the framework/version matrix. Locally, you only need one supported PHP runtime for development, but because PHPUnit 12 requires PHP 8.3+, your local test command must use PHP 8.3 or 8.4.

```bash
# Example with Homebrew default PHP
/opt/homebrew/bin/php vendor/bin/phpunit --configuration phpunit.xml
```

### Troubleshooting: "Cannot redeclare class" errors

If you see a `Cannot redeclare class CreateReferral*` error when running tests, stale migration files may have been published into the testbench `laravel/database/migrations/` directory. Remove them:

```bash
find vendor/orchestra/testbench-core/laravel/database/migrations/ -name "*.php" -delete
```

Then re-run your test command. For example, `/opt/homebrew/bin/php vendor/bin/phpunit --configuration phpunit.xml`.

## Development Workflow

1. **Fork** the repository on GitHub.
2. **Create a branch** from `master`:
   ```bash
   git checkout -b fix/your-fix-description
   # or
   git checkout -b feat/your-feature-description
   # or
   git checkout -b docs/your-docs-update
   ```
3. **Make your changes.**
4. **Run the tests** — all must pass.
5. **Push** your branch and open a Pull Request against `master`.

## Project Map

Use this structure when deciding where code, tests, or docs belong:

```text
src/
  Console/      artisan install command
  Events/       package events that integrations dispatch or listen for
  Http/         middleware that captures referral codes
  Listeners/    relationship creation and reward execution
  Models/       referral programs, links, and relationships
  Programs/     built-in reward program implementations
  Providers/    service provider, bindings, event wiring, AboutCommand entry
  Traits/       user-facing helper methods such as registerWithCode()
tests/Unit/     package behavior tests grouped by domain
docs/           integration guides, release notes, and research notes
```

## Adding Tests

Tests live in `tests/Unit/` and are organised by package namespace:

```
tests/
├── TestCase.php          # Orchestra Testbench base class
├── TestUser.php          # Minimal Eloquent User model for tests
├── WithLoadMigrations.php # Trait: runs package migrations in each test
└── Unit/
    ├── Events/           # Event dispatch tests
    ├── Listeners/        # ReferUser / RewardUser listener tests
    ├── Models/           # ReferralLink model tests
    └── MiddlewareTest.php # StoreReferralCode middleware tests
```

To add a new test, extend `Pdazcom\Referrals\Tests\TestCase` and use the `WithLoadMigrations` trait when you need database access:

```php
<?php

namespace Pdazcom\Referrals\Tests\Unit;

use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class MyNewTest extends TestCase
{
    use WithLoadMigrations;

    public function testSomething(): void
    {
        $user = $this->user(); // creates a User via the factory helper
        $this->assertTrue(true);
    }
}
```

## Useful Artisan Commands (in a host Laravel app)

Once the package is installed in a Laravel application:

```bash
# Publish config and migrations in one step
php artisan referrals:install

# Publish config only
php artisan referrals:install --config

# Publish migrations only
php artisan referrals:install --migrations

# Run migrations
php artisan migrate
```

## Code Style

- Follow PSR-12.
- Use PHP 8.2+ syntax (constructor property promotion, match expressions, named arguments).
- Keep public API surface minimal — favour adding to existing classes over new ones.

## Reporting Bugs

Open a [GitHub Issue](https://github.com/pdazcom/laravel-referrals/issues) and include:

- PHP version and Laravel version
- Steps to reproduce
- Expected vs actual behaviour

For security vulnerabilities, email `kostya.dn@gmail.com` instead of using the issue tracker.
