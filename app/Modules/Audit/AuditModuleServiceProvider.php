<?php

declare(strict_types=1);

namespace App\Modules\Audit;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\Providers\AuditEventServiceProvider;
use App\Modules\Audit\Domain\Contracts\RecordLoader;
use App\Modules\Audit\Domain\Contracts\ReferenceNames;
use App\Modules\Audit\Domain\Contracts\RetentionPeriod;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use App\Modules\Audit\Infrastructure\Persistence\EloquentChangeRepository;
use App\Modules\Audit\Infrastructure\Persistence\EloquentRecordLoader;
use App\Modules\Audit\Infrastructure\Persistence\EloquentReferenceNames;
use App\Modules\Audit\Infrastructure\Settings\ConfigRetentionPeriod;
use App\Modules\Audit\UserInterface\Console\CleanUpExpiredChangesConsoleCommand;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;
use Illuminate\Console\Scheduling\Schedule;

class AuditModuleServiceProvider extends ModuleServiceProvider
{
    protected bool $loadsTranslations = true;

    /** @var array<class-string> */
    protected array $eventProviders = [
        AuditEventServiceProvider::class,
    ];

    /** @var array<class-string, class-string> */
    protected array $containerBindings = [
        ChangeRepository::class => EloquentChangeRepository::class,
        RecordLoader::class => EloquentRecordLoader::class,
        ReferenceNames::class => EloquentReferenceNames::class,
        RetentionPeriod::class => ConfigRetentionPeriod::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AuditableRecords::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->declarePermissions($this->app->make(RoleRegistry::class));

        if ($this->app->runningInConsole()) {
            $this->commands([CleanUpExpiredChangesConsoleCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('audit:cleanup-retention')->dailyAt('03:00');
        });
    }

    private function declarePermissions(RoleRegistry $registry): void
    {
        $registry->declarePermissions(AuditPermission::class);
        // The other three are granted to no Role: Super Admin holds them by wildcard, others as Extra Permissions (D-41).
        $registry->grant(UserRole::Admin, AuditPermission::RecordHistory);
    }
}
