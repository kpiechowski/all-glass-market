<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Migrations\Migrator;

/**
 * Moves the test schema one migration at a time, so a data-transforming migration
 * can be run against rows shaped like the schema it actually meets in production.
 */
trait MigratesStepByStep
{
    /**
     * Every registered migration, in the order `migrate` runs them.
     *
     * @return array<string, string> migration name => file path
     */
    private function migrationFiles(): array
    {
        $migrator = $this->migrator();

        return $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
    }

    private function migration(string $name): Migration
    {
        $path = $this->migrationFiles()[$name] ?? null;
        $this->assertNotNull($path, "Migration {$name} is not registered.");

        return require $path;
    }

    /**
     * Runs down() on every migration after $name and on $name itself, newest first,
     * leaving the schema exactly as it was just before $name ran.
     */
    private function rollBackThrough(string $name): void
    {
        $names = array_keys($this->migrationFiles());
        $this->assertContains($name, $names, "Migration {$name} is not registered.");

        foreach (array_reverse(array_slice($names, array_search($name, $names, true))) as $later) {
            $this->migration($later)->down();
        }
    }

    private function migrator(): Migrator
    {
        return app('migrator');
    }
}
