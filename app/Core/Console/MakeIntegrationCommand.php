<?php

declare(strict_types=1);

namespace App\Core\Console;

use App\Core\Console\Concerns\ScaffoldsFromStubs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeIntegrationCommand extends Command
{
    use ScaffoldsFromStubs;

    protected $signature = 'make:integration
        {name : Integration name in PascalCase, typically the external platform (e.g. Otomoto, WooCommerce)}
        {--singular= : Singular entity name (defaults to Str::singular of name)}';

    protected $description = 'Scaffold an integration under app/Integrations: listener, API client, payload mapper and optional local persistence';

    private string $moduleName;

    private string $singularName;

    private string $rootNamespace = 'App\\Integrations';

    private bool $bindsRepository;

    public function handle(): int
    {
        $this->moduleName = $this->argument('name');
        $this->singularName = $this->option('singular') ?: Str::studly(Str::singular($this->moduleName));
        $basePath = app_path('Integrations'.DIRECTORY_SEPARATOR.$this->moduleName);

        if (File::exists($basePath)) {
            $this->error("Integration directory already exists at [{$basePath}].");

            return self::FAILURE;
        }

        $this->bindsRepository = $this->confirm('Include local persistence (entity, port, model, adapter, migration)?', true);
        $withApiClient = $this->confirm('Include API client (Infrastructure/Api)?', true);
        $withMapper = $this->confirm('Include payload mapper (Infrastructure/Mappers)?', true);

        $this->info("Scaffolding integration [{$this->moduleName}] at {$basePath}");

        $files = [
            "{$this->moduleName}IntegrationServiceProvider.php" => 'integration-service-provider.stub',
            "Application/Providers/{$this->moduleName}EventServiceProvider.php" => 'integration-event-service-provider.stub',
            "Application/Listeners/{$this->singularName}EventListener.php" => 'listener.stub',
        ];

        if ($withApiClient) {
            $files["Infrastructure/Api/{$this->moduleName}ApiClient.php"] = 'api-client.stub';
        }

        if ($withMapper) {
            $files["Infrastructure/Mappers/{$this->singularName}PayloadMapper.php"] = 'api-mapper.stub';
        }

        $this->writeStubs($basePath, $files);

        $tests = ["Unit/Integrations/{$this->moduleName}/Application/Listeners/{$this->singularName}EventListenerTest.php" => 'test-listener.stub'];

        if ($withMapper) {
            $tests["Unit/Integrations/{$this->moduleName}/Infrastructure/Mappers/{$this->singularName}PayloadMapperTest.php"] = 'test-payload-mapper.stub';
        }

        $this->writeStubs(base_path('tests'), $tests);

        if ($this->bindsRepository) {
            $this->writeStubs($basePath, $this->persistenceFiles());
            $this->writeStubs(base_path('tests'), $this->persistenceTestFiles());
        }

        $this->newLine();
        $this->info("<fg=green>Integration [{$this->moduleName}] scaffolded.</>");
        $this->line('  Folders are created when their first class arrives (module-layout skill).');

        return self::SUCCESS;
    }
}
