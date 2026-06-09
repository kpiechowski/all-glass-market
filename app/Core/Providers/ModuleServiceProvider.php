<?php

declare(strict_types=1);

namespace App\Core\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Abstract base provider for all domain modules.
 *
 * Extend this in your module root and configure via protected flags:
 *
 *   class BlogModuleServiceProvider extends ModuleServiceProvider { }
 *
 * Flags (all default to shown value):
 *
 *   protected bool $loadsMigrations   = true;   // Infrastructure/migrations
 *   protected bool $loadsConfig       = true;   // Infrastructure/config
 *   protected bool $loadsViews        = true;   // UserInterface/resources/views  (namespace: module-name::)
 *   protected bool $loadsTranslations = false;  // UserInterface/resources/lang  (namespace: module-name::)
 *   protected bool $registersMorphMap = true;   // Domain/Models auto morph map
 *
 * Container bindings:
 *
 *   protected array $containerBindings = [
 *       MyInterface::class => MyImplementation::class,
 *   ];
 *
 * Event/observer providers to register explicitly:
 *
 *   protected array $eventProviders = [
 *       BlogEventServiceProvider::class,
 *   ];
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    protected bool $loadsMigrations = true;

    protected bool $loadsConfig = true;

    protected bool $loadsViews = true;

    protected bool $loadsTranslations = false;

    protected bool $registersMorphMap = true;

    /** @var array<class-string, class-string> */
    protected array $containerBindings = [];

    /** @var array<class-string> */
    protected array $eventProviders = [];

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function register(): void
    {
        $this->registerContainerBindings();
        $this->registerEventProviders();
    }

    public function boot(): void
    {
        if ($this->loadsMigrations) {
            $this->loadMigrationsIfExists();
        }

        if ($this->loadsConfig) {
            $this->loadConfigIfExists();
        }

        if ($this->loadsViews) {
            $this->loadViewsIfExists();
        }

        if ($this->loadsTranslations) {
            $this->loadTranslationsIfExists();
        }

        if ($this->registersMorphMap) {
            $this->registerMorphMap();
        }
    }

    // -------------------------------------------------------------------------
    // Overridable helpers
    // -------------------------------------------------------------------------

    /**
     * Used as view/translation namespace. Defaults to the module directory name in snake_case.
     */
    protected function moduleName(): string
    {
        return Str::snake(basename($this->modulePath()));
    }

    /**
     * Absolute path to the module root (directory containing this provider file).
     * Pass a suffix to get a sub-path: $this->modulePath('/Domain/Models').
     */
    protected function modulePath(string $suffix = ''): string
    {
        $root = dirname((new ReflectionClass(static::class))->getFileName());

        return $root.$suffix;
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function registerContainerBindings(): void
    {
        foreach ($this->containerBindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    private function registerEventProviders(): void
    {
        foreach ($this->eventProviders as $provider) {
            $this->app->register($provider);
        }
    }

    private function loadMigrationsIfExists(): void
    {
        $path = $this->modulePath('/Infrastructure/migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function loadConfigIfExists(): void
    {
        $path = $this->modulePath('/Infrastructure/config');

        if (! is_dir($path)) {
            return;
        }

        foreach (glob($path.'/*.php') as $file) {
            $this->mergeConfigFrom($file, basename($file, '.php'));
        }
    }

    private function loadViewsIfExists(): void
    {
        $path = $this->modulePath('/UserInterface/resources/views');

        if (is_dir($path)) {
            $this->loadViewsFrom($path, $this->moduleName());
        }
    }

    private function loadTranslationsIfExists(): void
    {
        $path = $this->modulePath('/UserInterface/resources/lang');

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, $this->moduleName());
        }
    }

    /**
     * Scans Domain/Models/*.php and registers a morph map using snake_case model names.
     * Namespace is inferred from the concrete provider's namespace + \Domain\Models.
     */
    private function registerMorphMap(): void
    {
        $modelsPath = $this->modulePath('/Domain/Models');

        if (! is_dir($modelsPath)) {
            return;
        }

        $namespace = (new ReflectionClass(static::class))->getNamespaceName().'\\Domain\\Models';
        $map = [];

        foreach (glob($modelsPath.'/*.php') as $file) {
            $className = basename($file, '.php');
            $fqcn = $namespace.'\\'.$className;

            if (class_exists($fqcn)) {
                $map[Str::snake($className)] = $fqcn;
            }
        }

        if (! empty($map)) {
            Relation::morphMap($map, merge: true);
        }
    }
}
