<?php

namespace Pdazcom\Referrals\Tests\Unit\Console;

use Pdazcom\Referrals\Console\InstallCommand;
use Pdazcom\Referrals\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $migrationsDir = database_path('migrations');
        if (is_dir($migrationsDir)) {
            foreach (glob($migrationsDir . '/*.php') as $file) {
                unlink($file);
            }
        }

        $configFile = config_path('referrals.php');
        if (file_exists($configFile)) {
            unlink($configFile);
        }

        parent::tearDown();
    }

    public function testInstallCommandIsRegistered(): void
    {
        $commands = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all();

        $this->assertArrayHasKey('referrals:install', $commands);
        $this->assertInstanceOf(InstallCommand::class, $commands['referrals:install']);
    }

    public function testInstallCommandSucceeds(): void
    {
        $this->artisan('referrals:install')
            ->assertExitCode(0);
    }

    public function testInstallCommandWithConfigFlagSucceeds(): void
    {
        $this->artisan('referrals:install', ['--config' => true])
            ->assertExitCode(0);
    }

    public function testInstallCommandWithMigrationsFlagSucceeds(): void
    {
        $this->artisan('referrals:install', ['--migrations' => true])
            ->assertExitCode(0);
    }

    public function testInstallCommandPublishesMigrations(): void
    {
        $this->artisan('referrals:install', ['--migrations' => true]);

        $migrationsDir = database_path('migrations');
        $files = glob($migrationsDir . '/*.php');

        $this->assertNotEmpty($files, 'Migration files should be published.');
        $this->assertCount(4, $files);
    }

    public function testInstallCommandPublishesConfig(): void
    {
        $configFile = config_path('referrals.php');
        $this->assertFileDoesNotExist($configFile);

        $this->artisan('referrals:install', ['--config' => true]);

        $this->assertFileExists($configFile);
    }
}
