<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Console\CreateDevUserCommand;
use App\Core\Console\DeleteModuleCommand;
use App\Core\Console\MakeIntegrationCommand;
use App\Core\Console\MakeModuleCommand;
use App\Core\Contracts\CommandContext;
use App\Core\Contracts\PanelNotifier;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\UserInterface\FilamentPanelNotifier;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActorStack::class);
        $this->app->bind(CommandContext::class, ActorStack::class);
        $this->app->bind(PanelNotifier::class, FilamentPanelNotifier::class);

        $this->discoverModuleProviders();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Infrastructure/migrations');
        $this->loadTranslationsFrom(__DIR__.'/UserInterface/resources/lang', 'core');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateDevUserCommand::class,
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
