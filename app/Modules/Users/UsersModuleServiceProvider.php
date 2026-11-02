<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Contracts\Authorizer;
use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Users\Application\ModuleApi\JournalEntryUrls;
use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Application\ModuleApi\UserUrls;
use App\Modules\Users\Application\Providers\UsersEventServiceProvider;
use App\Modules\Users\Application\Services\UserAuthorizer;
use App\Modules\Users\Application\Services\UserJournalSubject;
use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Infrastructure\Auth\LaravelPasswordLinks;
use App\Modules\Users\Infrastructure\Persistence\EloquentJournalEntryRepository;
use App\Modules\Users\Infrastructure\Persistence\EloquentUserRepository;
use App\Modules\Users\UserInterface\Console\GrantSuperAdminConsoleCommand;
use App\Modules\Users\UserInterface\Console\RevokeSuperAdminConsoleCommand;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support\FilamentJournalEntryUrls;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\FilamentUserUrls;
use App\Modules\Users\UserInterface\Livewire\ImpersonationBanner;
use Livewire\Livewire;

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
        UserUrls::class => FilamentUserUrls::class,
        PasswordLinks::class => LaravelPasswordLinks::class,
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
        $this->app->make(JournalSubjects::class)->register('users', $this->app->make(UserJournalSubject::class));

        Livewire::component('users-impersonation-banner', ImpersonationBanner::class);

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
        // Impersonate is granted to no built-in Role: Super Admin holds it by wildcard, an Admin as an Extra Permission (FR-28).
        $registry->grant(UserRole::Admin, UsersPermission::ManageUsers);
    }
}
