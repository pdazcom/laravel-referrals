<?php
namespace Pdazcom\Referrals\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Pdazcom\Referrals\Tests\TestUser as User;

trait WithLoadMigrations
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase()
    {
        $this->app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');


    }

    protected function user(array $attributes = []): User {
        return User::create([
            ...[
                'name' => fake()->userName,
                'email' => fake()->email,
                'password' => Hash::make(fake()->password),
            ],
            ...$attributes]);
    }
}
