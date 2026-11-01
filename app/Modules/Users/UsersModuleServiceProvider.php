<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Contracts\Authorizer;
use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Users\Application\ModuleApi\JournalEntryUrls;
use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Application\Providers\UsersEventServiceProvider;
use App\Modules\Users\Application\Services\UserAuthorizer;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Infrastructure\Persistence\EloquentJournalEntryRepository;
use App\Modules\Users\Infrastructure\Persistence\EloquentUserRepository;
use App\Modules\Users\UserInterface\Console\GrantSuperAdminConsoleCommand;
use App\Modules\Users\UserInterface\Console\RevokeSuperAdminConsoleCommand;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support\FilamentJournalEntryUrls;

class UsersModuleServiceProvider extends ModuleServiceProvider
{
    protected bool $loadsTranslations = true;

    /** @var array<class-string> */
    protected array $eventProviders = [
        UsersEventServiceProvider::class,
    ];

    /** @var array<class-string, class-string> */
    protected array $containerBindings = [
        Authorizer::class => UserAuthorizer::class,
        UserRepository::class => EloquentUserRepository::class,
        JournalEntryRepository::class => EloquentJournalEntryRepository::class,
        JournalEntryUrls::class => FilamentJournalEntryUrls::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(RoleRegistry::class);
        $this->app->singleton(JournalSubjects::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->declareRolesAndPermissions($this->app->make(RoleRegistry::class));

        if ($this->app->runningInConsole()) {
            $this->commands([
                GrantSuperAdminConsoleCommand::class,
                RevokeSuperAdminConsoleCommand::class,
            ]);
        }
    }

    private function declareRolesAndPermissions(RoleRegistry $registry): void
    {
        $registry->declareRole(UserRole::SuperAdmin, UserRole::SuperAdmin->rank(), holdsEveryPermission: true);
        $registry->declareRole(UserRole::Admin, UserRole::Admin->rank());
        $registry->declareRole(UserRole::User, UserRole::User->rank());

        $registry->declarePermissions(UsersPermission::class);
        $registry->grant(UserRole::Admin, UsersPermission::ManageUsers);
    }
}
