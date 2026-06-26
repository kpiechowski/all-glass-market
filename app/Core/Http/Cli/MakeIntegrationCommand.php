<?php

declare(strict_types=1);

namespace App\Core\Http\Cli;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeIntegrationCommand extends Command
{
    protected $signature = 'make:integration
        {name : Integration name in PascalCase, typically the external platform (e.g. Otomoto, WooCommerce)}
        {--singular= : Singular entity name used for Model/Repository/Mapper (defaults to Str::singular of name)}';

    protected $description = 'Scaffold a new integration module under app/Integrations with full DDD structure';

    private const ROOT_NAMESPACE = 'App\\Integrations';

    private string $integrationName;

    private string $singularName;

    private string $basePath;

    private bool $withModels;

    private bool $withApiClient;

    private bool $withMapper;

    private bool $withFilament;

    private bool $withTranslations;

    private bool $withEventProvider;

    public function handle(): int
    {
        $this->integrationName = $this->argument('name');
        $this->singularName    = $this->option('singular') ?: Str::studly(Str::singular($this->integrationName));
        $this->basePath        = app_path('Integrations' . DIRECTORY_SEPARATOR . $this->integrationName);

        $this->info("Scaffolding integration : <fg=cyan>{$this->integrationName}</>");
        $this->info("Singular entity         : <fg=cyan>{$this->singularName}</>");
        $this->info("Namespace               : <fg=cyan>" . self::ROOT_NAMESPACE . "\\{$this->integrationName}</>");
        $this->info("Target path             : <fg=cyan>{$this->basePath}</>");
        $this->newLine();

        if (File::exists($this->basePath)) {
            $this->error("Integration directory already exists at [{$this->basePath}].");

            return self::FAILURE;
        }

        // ── Feature selection ────────────────────────────────────────────────
        $this->withModels       = $this->confirm('Include domain models, migrations, factories and repositories?', true);
        $this->withApiClient    = $this->confirm('Include API client (Infrastructure/Api)?', true);
        $this->withMapper       = $this->confirm('Include data mapper (Domain/Mappers)?', true);
        $this->withFilament     = $this->confirm('Include Filament UI (UserInterface/Filament)?', true);
        $this->withTranslations = $this->confirm('Include translations (UserInterface/resources/lang)?', false);
        $this->withEventProvider = $this->confirm('Include event service provider?', true);

        $this->newLine();

        $this->createDirectories();
        $this->generatePhpFiles();

        $this->newLine();
        $this->info("<fg=green>Integration [{$this->integrationName}] scaffolded successfully.</>");

        if ($this->withFilament && $this->withModels) {
            $this->info(
                '<fg=green>Next step:</> php artisan make:filament-resource ' . $this->singularName .
                ' --generate --model-namespace="' . self::ROOT_NAMESPACE . '\\' . $this->integrationName . '\\Domain\\Models"'
            );
        }

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Directory structure
    // ─────────────────────────────────────────────────────────────────────────

    private function createDirectories(): void
    {
        // Always-present directories
        $dirs = [
            // Application layer — listeners are the primary entry point for integrations
            'Application/Commands',
            'Application/Jobs',
            'Application/Listeners',
            'Application/Providers',
            'Application/Services',
            // Domain layer
            'Domain/Dto',
            'Domain/Enums',
            'Domain/Events',
            'Domain/ValueObjects',
            // Infrastructure base
            'Infrastructure/config',
            // Views
            'UserInterface/resources/views',
        ];

        if ($this->withModels) {
            array_push(
                $dirs,
                'Domain/Models',
                'Domain/Repositories',
                'Infrastructure/Factories',
                'Infrastructure/migrations',
                'Infrastructure/Seeders',
            );
        }

        if ($this->withApiClient) {
            $dirs[] = 'Infrastructure/Api';
        }

        if ($this->withMapper) {
            $dirs[] = 'Domain/Mappers';
        }

        if ($this->withFilament) {
            array_push(
                $dirs,
                'UserInterface/Filament/Clusters',
                'UserInterface/Filament/Pages',
                'UserInterface/Filament/Resources',
                'UserInterface/Filament/Widgets',
            );
        }

        if ($this->withTranslations) {
            $dirs[] = 'UserInterface/resources/lang';
        }

        foreach ($dirs as $dir) {
            $fullPath = $this->basePath . DIRECTORY_SEPARATOR . $dir;
            File::makeDirectory($fullPath, 0755, true, true);
            File::put($fullPath . '/.gitkeep', '');
        }

        $this->line('  <fg=green>✓</> Directory structure created.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PHP file generation
    // ─────────────────────────────────────────────────────────────────────────

    private function generatePhpFiles(): void
    {
        $files = [
            "{$this->integrationName}IntegrationServiceProvider.php" => 'integration-service-provider.stub',
            "Application/Services/{$this->integrationName}Service.php" => 'service.stub',
            "Application/Listeners/{$this->singularName}DomainEventListener.php" => 'listener.stub',
        ];

        if ($this->withModels) {
            $files["Domain/Models/{$this->singularName}.php"]                       = 'model.stub';
            $files["Domain/Repositories/{$this->singularName}Repository.php"]       = 'repository.stub';
            $files["Infrastructure/Factories/{$this->singularName}Factory.php"]     = 'factory.stub';
        }

        if ($this->withApiClient) {
            $files["Infrastructure/Api/{$this->integrationName}ApiClient.php"] = 'api-client.stub';
        }

        if ($this->withMapper) {
            $files["Domain/Mappers/{$this->singularName}Mapper.php"] = 'mapper.stub';
        }

        if ($this->withEventProvider) {
            $files["Application/Providers/{$this->integrationName}EventServiceProvider.php"] = 'integration-event-service-provider.stub';
        }

        foreach ($files as $relativePath => $stubName) {
            $this->writeFromStub($stubName, $relativePath);
        }
    }

    private function writeFromStub(string $stubName, string $relativePath): void
    {
        $stubFile = __DIR__ . '/../../Support/Stubs/' . $stubName;

        if (! File::exists($stubFile)) {
            $this->warn("  Stub not found – skipping: {$stubName}");

            return;
        }

        $content = File::get($stubFile);

        $content = str_replace('{{ModuleName}}',       $this->integrationName, $content);
        $content = str_replace('{{SingularName}}',     $this->singularName,    $content);
        $content = str_replace('{{RootNamespace}}',    self::ROOT_NAMESPACE,   $content);
        $content = str_replace('{{LoadsMigrations}}',  $this->withModels       ? 'true' : 'false', $content);
        $content = str_replace('{{LoadsTranslations}}', $this->withTranslations ? 'true' : 'false', $content);

        $eventImport = $this->withEventProvider
            ? 'use ' . self::ROOT_NAMESPACE . "\\{$this->integrationName}\\Application\\Providers\\{$this->integrationName}EventServiceProvider;\n"
            : '';
        $eventProviders = $this->withEventProvider
            ? "\n        {$this->integrationName}EventServiceProvider::class,\n    "
            : '';

        $content = str_replace('{{EventProviderImports}}', $eventImport,    $content);
        $content = str_replace('{{EventProviders}}',       $eventProviders, $content);

        $targetPath = $this->basePath . DIRECTORY_SEPARATOR . $relativePath;

        $gitkeep = dirname($targetPath) . '/.gitkeep';
        if (File::exists($gitkeep)) {
            File::delete($gitkeep);
        }

        File::put($targetPath, $content);
        $this->line("  <fg=green>✓</> {$relativePath}");
    }
}
