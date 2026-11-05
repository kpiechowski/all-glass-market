<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Contracts\Authorizer;
use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\TrackedRelation;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Users\Application\ModuleApi\JournalEntryUrls;
use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Application\ModuleApi\UserUrls;
use App\Modules\Users\Application\Providers\UsersEventServiceProvider;
use App\Modules\Users\Application\Services\UserAuthorizer;
use App\Modules\Users\Application\Services\UserJournalSubject;
use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Infrastructure\Auth\LaravelPasswordLinks;
use App\Modules\Users\Infrastructure\Persistence\EloquentJournalEntryRepository;
use App\Modules\Users\Infrastructure\Persistence\EloquentUserRepository;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Console\GrantSuperAdminConsoleCommand;
use App\Modules\Users\UserInterface\Console\RevokeSuperAdminConsoleCommand;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support\FilamentJournalEntryUrls;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\FilamentUserUrls;
use App\Modules\Users\UserInterface\Livewire\ImpersonationBanner;
use Livewire\Livewire;

class UsersModuleServiceProvider extends ModuleServiceProvider
{
    /** Stable name stored in every entry: never rename it. */
    private const string AUDIT_KEY = 'users.user';

    /** Secrets, tokens and timestamps that are not business data. */
    private const array AUDIT_EXCLUDED_FIELDS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'invitation_token',
        'reset_token',
        'email_verified_at',
        'account_type_changed_at',
        'extra_permissions', // reported as a list change by the repository adapter
    ];

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

        $this->registerAuditableRecords($this->app->make(AuditableRecords::class));

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

    private function registerAuditableRecords(AuditableRecords $records): void
    {
        $records->register(new AuditableRecordType(
            key: self::AUDIT_KEY,
            modelClass: UserModel::class,
            name: 'users::audit.type',
            title: fn (UserModel $user): string => $user->name,
            url: fn (UserModel $user): string => app(UserUrls::class)->show((int) $user->getKey()),
            excludedFields: self::AUDIT_EXCLUDED_FIELDS,
            fieldLabels: $this->fieldLabels(),
            valuePresenters: [
                'role' => ValuePresenter::enum(UserRole::class),
                'account_type' => ValuePresenter::enum(AccountType::class),
                'seller_declaration_accepted_at' => ValuePresenter::date(),
                'deactivated_at' => ValuePresenter::date(),
                'password_change_required' => ValuePresenter::boolean(),
            ],
            trackedRelations: [
                'extra_permissions' => new TrackedRelation('users::audit.relations.extra_permissions', ValuePresenter::translated('users::permissions.')),
            ],
        ));
    }

    /** @return array<string, string> */
    private function fieldLabels(): array
    {
        $fields = ['name', 'email', 'phone', 'company_name', 'company_nip', 'company_address', 'shipment_address', 'city', 'city_code', 'shipping_note', 'role', 'account_type', 'seller_declaration_accepted_at', 'seller_declaration_version', 'deactivated_at', 'password_change_required'];

        return array_combine($fields, array_map(fn (string $field): string => 'users::audit.fields.'.$field, $fields));
    }
}
