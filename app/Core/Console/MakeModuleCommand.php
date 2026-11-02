<?php

declare(strict_types=1);

namespace App\Core\Console;

use App\Core\Console\Concerns\ScaffoldsFromStubs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    use ScaffoldsFromStubs;

    protected $signature = 'make:module
        {name : The module name in PascalCase, typically plural (e.g. Offers)}
        {--namespace=App\Modules : Root namespace where the module will live}
        {--singular= : Singular entity name (defaults to Str::singular of name)}';

    protected $description = 'Scaffold a ports & adapters module: entity, port, adapter, model, translations and test skeletons';

    private string $moduleName;

    private string $singularName;

    private string $rootNamespace;

    private bool $bindsRepository = true;

    public function handle(): int
    {
        $this->moduleName = $this->argument('name');
        $this->singularName = $this->option('singular') ?: Str::studly(Str::singular($this->moduleName));
        $this->rootNamespace = rtrim($this->option('namespace'), '\\');
        $basePath = $this->resolveBasePath($this->rootNamespace).DIRECTORY_SEPARATOR.$this->moduleName;

        if (File::exists($basePath)) {
            $this->error("Module directory already exists at [{$basePath}].");

            return self::FAILURE;
        }

        $this->info("Scaffolding module [{$this->moduleName}] (entity {$this->singularName}) at {$basePath}");

        $this->writeStubs($basePath, $this->moduleFiles());
        $this->writeStubs(base_path('tests'), $this->persistenceTestFiles() + $this->moduleTestFiles());

        $this->newLine();
        $this->info("<fg=green>Module [{$this->moduleName}] scaffolded.</>");
        $this->line('  Folders are created when their first class arrives (module-layout skill).');
        $this->line("  Filament: dartisan make:filament-resource {$this->singularName} --model-namespace=\"{$this->rootNamespace}\\{$this->moduleName}\\Infrastructure\\Persistence\\Models\" --no-interaction,");
        $this->line('  then move it under UserInterface/Filament/Resources/ (filament-module-ui skill).');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function moduleFiles(): array
    {
        $x = $this->singularName;
        $n = $this->moduleName;

        return $this->persistenceFiles() + [
            "{$n}ModuleServiceProvider.php" => 'module-service-provider.stub',
            "Application/Providers/{$n}EventServiceProvider.php" => 'event-service-provider.stub',
            "Domain/Enums/{$n}Permission.php" => 'permission.stub',
            "Infrastructure/Persistence/Observers/{$x}Observer.php" => 'observer.stub',
            "Infrastructure/Persistence/Queries/{$x}Queries.php" => 'queries.stub',
            'UserInterface/resources/lang/pl/resources.php' => 'lang-resources.stub',
            'UserInterface/resources/lang/pl/enums.php' => 'lang-enums.stub',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function moduleTestFiles(): array
    {
        $path = "Feature/Modules/{$this->moduleName}/Infrastructure/Persistence";

        return [
            "{$path}/Observers/{$this->singularName}ObserverTest.php" => 'test-observer.stub',
            "{$path}/Queries/{$this->singularName}QueriesTest.php" => 'test-queries.stub',
        ];
    }

    private function resolveBasePath(string $namespace): string
    {
        $parts = explode('\\', $namespace);

        if ($parts[0] === 'App') {
            $relative = implode(DIRECTORY_SEPARATOR, array_slice($parts, 1));

            return $relative === '' ? app_path() : app_path($relative);
        }

        return base_path(implode(DIRECTORY_SEPARATOR, $parts));
    }
}
