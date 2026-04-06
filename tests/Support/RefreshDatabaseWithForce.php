<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Same as RefreshDatabase, but always passes --force to migrate / migrate:fresh.
 * Without this, tests fail on servers where APP_ENV=production (interactive confirm + Mockery OutputStyle).
 */
trait RefreshDatabaseWithForce
{
    use RefreshDatabase {
        migrateFreshUsing as baseMigrateFreshUsing;
        migrateUsing as baseMigrateUsing;
    }

    protected function migrateFreshUsing()
    {
        return array_merge($this->baseMigrateFreshUsing(), [
            '--force' => true,
        ]);
    }

    protected function migrateUsing()
    {
        return array_merge($this->baseMigrateUsing(), [
            '--force' => true,
        ]);
    }
}
