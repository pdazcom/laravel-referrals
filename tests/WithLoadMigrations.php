<?php
namespace Pdazcom\Referrals\Tests;

use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;

trait WithLoadMigrations
{
    use WithLoadMigrationsFrom;

    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
