<?php

namespace Pdazcom\Referrals\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'referrals:install
                            {--config : Publish the config file}
                            {--migrations : Publish the migration files}';

    protected $description = 'Publish the Laravel Referrals config and migrations';

    public function handle(): int
    {
        $publishConfig = $this->option('config');
        $publishMigrations = $this->option('migrations');

        $publishAll = ! $publishConfig && ! $publishMigrations;

        if ($publishAll || $publishConfig) {
            $this->publishConfig();
        }

        if ($publishAll || $publishMigrations) {
            $this->publishMigrations();
        }

        $this->components->info('Laravel Referrals installed successfully.');
        $this->newLine();
        $this->components->bulletList([
            'Run <comment>php artisan migrate</comment> to create the referrals tables.',
            'Edit <comment>config/referrals.php</comment> to configure your referral programs.',
        ]);

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        $this->components->task('Publishing config', function () {
            $this->callSilently('vendor:publish', [
                '--tag' => 'referrals-config',
                '--force' => false,
            ]);
        });
    }

    private function publishMigrations(): void
    {
        $this->components->task('Publishing migrations', function () {
            $this->callSilently('vendor:publish', [
                '--tag' => 'referrals-migrations',
                '--force' => false,
            ]);
        });
    }
}
