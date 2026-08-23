<?php

declare(strict_types=1);

namespace App\Core\Http\Cli;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {name : The module name in PascalCase, typically plural (e.g. CommerceUsers)}
        {--namespace=App\Modules : Root namespace where the module will live}
        {--singular= : Singular entity name used for Model/Observer/Repository (defaults to Str::singular of name)}';

    protected $description = 'Scaffold a new ports & adapters module with full folder structure and starter files';

    private string $moduleName;

    private string $singularName;

    private string $rootNamespace;

    private string $basePath;

    private bool $withModels;

    private bool $withTranslations;

    private bool $withEventProvider;

    public function handle(): int
    {
        $this->moduleName = $this->argument('name');
        $this->singularName = $this->option('singular') ?: Str::studly(Str::singular($this->moduleName));
        $this->rootNamespace = rtrim($this->option('namespace'), '\\');
        $this->basePath = $this->resolveBasePath($this->rootNamespace).DIRECTORY_SEPARATOR.$this->moduleName;

        $this->info("Scaffolding module : <fg=cyan>{$this->moduleName}</>");
        $this->info("Singular entity    : <fg=cyan>{$this->singularName}</>");
        $this->info("Root namespace     : <fg=cyan>{$this->rootNamespace}\\{$this->moduleName}</>");
        $this->info("Target path        : <fg=cyan>{$this->basePath}</>");
        $this->newLine();

        if (File::exists($this->basePath)) {
            $this->error("Module directory already exists at [{$this->basePath}].");

            return self::FAILURE;
        }

        // ── Feature selection ────────────────────────────────────────────────
        $this->withModels = $this->confirm('Include domain models, migrations, factories, observers and repository port + adapter?', true);
        $this->withTranslations = $this->confirm('Include translations (UserInterface/resources/lang)?', false);
        $this->withEventProvider = $this->confirm('Include event service provider?', true);

        $this->newLine();

        $this->createDirectories();
        $this->generatePhpFiles();

        $this->newLine();
        $this->info("<fg=green>Module [{$this->moduleName}] scaffolded successfully.</>");
        $this->info("<fg=green>Proceed with Filament resource using </> dartisan make:filament-resource {$this->singularName} --generate --model-namespace=\"{$this->rootNamespace}\\{$this->moduleName}\\Domain\\Models\"");
        $this->line('  Then move it under UserInterface/Filament/Resources/ — see the module-layout skill.');

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Directory structure
    // ─────────────────────────────────────────────────────────────────────────

    private function createDirectories(): void
    {
        $dirs = [
            // Application layer — what the system does
            'Application/Commands',
            'Application/Listeners',
            'Application/Providers',
            'Application/Queries',
            'Application/Services',
            // Domain layer (always present) — what the thing is
            'Domain/Enums',
            'Domain/Events',
            'Domain/Exceptions',
            'Domain/ValueObjects',
            // Filament PHP classes — must exist for panel auto-discovery and make:filament-* targeting
            'UserInterface/Filament/Clusters',
            'UserInterface/Filament/Pages',
            'UserInterface/Filament/Resources',
            'UserInterface/Filament/Widgets',
            // Views — registered automatically by ModuleServiceProvider (namespace: module-name::)
            'UserInterface/resources/views',
        ];

        if ($this->withModels) {
            array_push(
                $dirs,
                'Application/Dto',
                'Domain/Models',
                'Domain/Policies',
                'Domain/Repositories',
                'Domain/Traits',
                'Infrastructure/config',
                'Infrastructure/Factories',
                'Infrastructure/migrations',
                'Infrastructure/Persistence/Observers',
                'Infrastructure/Seeders',
            );
        }

        if ($this->withTranslations) {
            $dirs[] = 'UserInterface/resources/lang';
        }

        foreach ($dirs as $dir) {
            $fullPath = $this->basePath.DIRECTORY_SEPARATOR.$dir;
            File::makeDirectory($fullPath, 0755, true, true);
            File::put($fullPath.'/.gitkeep', '');
        }

        $this->line('  <fg=green>✓</> Directory structure created.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PHP file generation
    // ─────────────────────────────────────────────────────────────────────────

    private function generatePhpFiles(): void
    {
        $files = [
            "{$this->moduleName}ModuleServiceProvider.php" => 'module-service-provider.stub',
            "Application/Services/{$this->moduleName}Service.php" => 'service.stub',
        ];

        if ($this->withModels) {
            $files["Domain/Models/{$this->singularName}.php"] = 'model.stub';
            $files["Domain/Policies/{$this->singularName}DomainPolicy.php"] = 'domain-policy.stub';
            $files["Domain/Repositories/{$this->singularName}Repository.php"] = 'repository.stub';
            $files["Infrastructure/Factories/{$this->singularName}Factory.php"] = 'factory.stub';
            $files["Infrastructure/Persistence/Eloquent{$this->singularName}Repository.php"] = 'eloquent-repository.stub';
            $files["Infrastructure/Persistence/Observers/{$this->singularName}Observer.php"] = 'observer.stub';
        }

        if ($this->withEventProvider) {
            $files["Application/Providers/{$this->moduleName}EventServiceProvider.php"] = 'event-service-provider.stub';
        }

        foreach ($files as $relativePath => $stubName) {
            $this->writeFromStub($stubName, $relativePath);
        }
    }

    private function writeFromStub(string $stubName, string $relativePath): void
    {
        $stubFile = __DIR__.'/../../Support/Stubs/'.$stubName;

        if (! File::exists($stubFile)) {
            $this->warn("  Stub not found – skipping: {$stubName}");

            return;
        }

        $content = File::get($stubFile);

        // Standard tokens
        $content = str_replace('{{ModuleName}}', $this->moduleName, $content);
        $content = str_replace('{{SingularName}}', $this->singularName, $content);
        $content = str_replace('{{RootNamespace}}', $this->rootNamespace, $content);

        // Feature flag tokens (used by module-service-provider.stub)
        $content = str_replace('{{LoadsMigrations}}', $this->withModels ? 'true' : 'false', $content);
        $content = str_replace('{{LoadsTranslations}}', $this->withTranslations ? 'true' : 'false', $content);

        $eventImport = $this->withEventProvider
            ? "use {$this->rootNamespace}\\{$this->moduleName}\\Application\\Providers\\{$this->moduleName}EventServiceProvider;\n"
            : '';
        $eventProviders = $this->withEventProvider
            ? "\n        {$this->moduleName}EventServiceProvider::class,\n    "
            : '';

        $content = str_replace('{{EventProviderImports}}', $eventImport, $content);
        $content = str_replace('{{EventProviders}}', $eventProviders, $content);

        // Port → adapter binding, only when the module has models
        $moduleNamespace = "{$this->rootNamespace}\\{$this->moduleName}";
        $bindingImports = $this->withModels
            ? "use {$moduleNamespace}\\Domain\\Repositories\\{$this->singularName}Repository;\n"
            ."use {$moduleNamespace}\\Infrastructure\\Persistence\\Eloquent{$this->singularName}Repository;\n"
            : '';
        $containerBindings = $this->withModels
            ? "\n        {$this->singularName}Repository::class => Eloquent{$this->singularName}Repository::class,\n    "
            : '';

        $content = str_replace('{{BindingImports}}', $bindingImports, $content);
        $content = str_replace('{{ContainerBindings}}', $containerBindings, $content);

        $targetPath = $this->basePath.DIRECTORY_SEPARATOR.$relativePath;

        $gitkeep = dirname($targetPath).'/.gitkeep';
        if (File::exists($gitkeep)) {
            File::delete($gitkeep);
        }

        File::put($targetPath, $content);
        $this->line("  <fg=green>✓</> {$relativePath}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convert a PHP namespace to an absolute filesystem path.
     *
     * Rules:
     *   App\*     → app_path() using Laravel's app/ directory
     *   Modules\* → app_path('Modules') matching PSR-4 config
     *   Anything else → base_path() from project root
     */
    private function resolveBasePath(string $namespace): string
    {
        $parts = explode('\\', $namespace);

        if ($parts[0] === 'App') {
            $relative = implode(DIRECTORY_SEPARATOR, array_slice($parts, 1));

            return $relative === '' ? app_path() : app_path($relative);
        }

        if ($parts[0] === 'Modules') {
            $relative = implode(DIRECTORY_SEPARATOR, array_slice($parts, 1));

            return $relative === '' ? app_path('Modules') : app_path('Modules'.DIRECTORY_SEPARATOR.$relative);
        }

        return base_path(implode(DIRECTORY_SEPARATOR, $parts));
    }
}
