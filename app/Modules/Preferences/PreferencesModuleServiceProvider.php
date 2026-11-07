<?php

declare(strict_types=1);

namespace App\Modules\Preferences;

use App\Core\Domain\Preferences\PreferenceDefinitions;
use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Preferences\Domain\Definitions\QuickAccessPreference;
use App\Modules\Preferences\Domain\Repositories\UserPreferenceRepository;
use App\Modules\Preferences\Infrastructure\Persistence\EloquentUserPreferenceRepository;

/**
 * ADR 0006: per-user preferences, each defined in code. Preferences knows no other module; it
 * registers its own first definition, the dashboard's quick-access tiles.
 */
class PreferencesModuleServiceProvider extends ModuleServiceProvider
{
    protected bool $loadsTranslations = true;

    /** @var array<class-string, class-string> */
    protected array $containerBindings = [
        UserPreferenceRepository::class => EloquentUserPreferenceRepository::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->app->make(PreferenceDefinitions::class)->register($this->app->make(QuickAccessPreference::class));
    }
}
