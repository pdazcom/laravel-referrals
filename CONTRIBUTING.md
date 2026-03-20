# Contributing to Laravel Referrals

Thank you for considering a contribution! This guide covers everything you need to get the project running locally and submit a pull request.

## Prerequisites

| Requirement | Version |
|---|---|
| PHP | 8.2, 8.3, or 8.4 |
| Composer | 2.x |
| SQLite extension | `pdo_sqlite` |

No database server is required — tests run against SQLite in-memory.

## Local Setup

```bash
git clone https://github.com/pdazcom/laravel-referrals.git
cd laravel-referrals
composer install
```

## Running Tests

```bash
vendor/bin/phpunit
```

All 13 tests must pass before you submit a PR. The test suite uses:

- **Orchestra Testbench** to bootstrap a minimal Laravel app
- **SQLite in-memory** — no external database needed
- **RefreshDatabase** — each test starts with a clean schema

### PHP version matrix

The project is tested against multiple Laravel versions in CI. Locally you only need one supported PHP version. If you have multiple PHP binaries (e.g. via Homebrew), call phpunit with the right one:

```bash
# Example with Homebrew default PHP
/opt/homebrew/bin/php vendor/bin/phpunit
```

### Troubleshooting: "Cannot redeclare class" errors

If you see a `Cannot redeclare class CreateReferral*` error when running tests, stale migration files may have been published into the testbench `laravel/database/migrations/` directory. Remove them:

```bash
find vendor/orchestra/testbench-core/laravel/database/migrations/ -name "*.php" -delete
```

Then re-run `vendor/bin/phpunit` — the error will be gone.

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
