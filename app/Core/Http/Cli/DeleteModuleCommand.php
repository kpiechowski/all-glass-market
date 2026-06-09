<?php

declare(strict_types=1);

namespace App\Core\Http\Cli;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteModuleCommand extends Command
{
    protected $signature = 'delete:module
        {name : The module name to delete (PascalCase, e.g. Orders)}
        {--namespace=App\Modules : Root namespace where the module lives}
        {--force : Skip confirmation prompt}';

    protected $description = 'Delete a scaffolded DDD module and all its files';

    public function handle(): int
    {
        $name = $this->argument('name');
        $namespace = rtrim($this->option('namespace'), '\\');
        $basePath = $this->resolveBasePath($namespace) . DIRECTORY_SEPARATOR . $name;

        if (!File::exists($basePath)) {
            $this->error("Module [{$name}] not found at [{$basePath}].");

            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $confirmed = $this->confirm(
                "This will permanently delete [{$basePath}] and everything inside. Continue?",
                false
            );

            if (!$confirmed) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        File::deleteDirectory($basePath);

        if (File::exists($basePath)) {
            $this->error("Failed to delete [{$basePath}]. Check permissions.");

            return self::FAILURE;
        }

        $this->info("Module [{$name}] deleted successfully.");

        return self::SUCCESS;
    }

    private function resolveBasePath(string $namespace): string
    {
        $parts = explode('\\', $namespace);

        if ($parts[0] === 'App') {
            $relative = implode(DIRECTORY_SEPARATOR, array_slice($parts, 1));

            return $relative === '' ? app_path() : app_path($relative);
        }

        if ($parts[0] === 'Modules') {
            $relative = implode(DIRECTORY_SEPARATOR, array_slice($parts, 1));

            return $relative === '' ? app_path('Modules') : app_path('Modules' . DIRECTORY_SEPARATOR . $relative);
        }

        return base_path(implode(DIRECTORY_SEPARATOR, $parts));
    }
}
