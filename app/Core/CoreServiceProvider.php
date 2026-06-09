<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Cli\DeleteModuleCommand;
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
        $this->loadMigrationsFrom(__DIR__ . '/Infrastructure/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DeleteModuleCommand::class,
                MakeModuleCommand::class,
            ]);
        }
    }

    private function discoverModuleProviders(): void
    {
        $modulesPath = app_path('Modules');

        if (!File::exists($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $dir) {
            $moduleName = basename($dir);
            $provider = $moduleName . 'ModuleServiceProvider';
            $fqcn = 'App\\Modules\\' . $moduleName . '\\' . $provider;

            if (File::exists($dir . '/' . $provider . '.php') && class_exists($fqcn)) {
                $this->app->register($fqcn);
            }
        }
    }
}
