<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Cli\CreateDevUserCommand;
use App\Core\Http\Cli\CreateRootCommand;
use App\Core\Http\Cli\DeleteModuleCommand;
use App\Core\Http\Cli\MakeIntegrationCommand;
use App\Core\Http\Cli\MakeModuleCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->discoverModuleProviders();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Infrastructure/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateDevUserCommand::class,
                CreateRootCommand::class,
                DeleteModuleCommand::class,
                MakeModuleCommand::class,
                MakeIntegrationCommand::class,
            ]);
        }
    }

    private function discoverModuleProviders(): void
    {
        $this->discoverProvidersIn(app_path('Modules'), 'App\\Modules', 'ModuleServiceProvider');
        $this->discoverProvidersIn(app_path('Integrations'), 'App\\Integrations', 'IntegrationServiceProvider');
    }

    private function discoverProvidersIn(string $basePath, string $baseNamespace, string $providerSuffix): void
    {
        if (! File::exists($basePath)) {
            return;
        }

        foreach (File::directories($basePath) as $dir) {
            $name = basename($dir);
            $provider = $name.$providerSuffix;
            $fqcn = $baseNamespace.'\\'.$name.'\\'.$provider;

            if (File::exists($dir.'/'.$provider.'.php') && class_exists($fqcn)) {
                $this->app->register($fqcn);
            }
        }
    }
}
