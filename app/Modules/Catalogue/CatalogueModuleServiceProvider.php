<?php

declare(strict_types=1);

namespace App\Modules\Catalogue;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Catalogue\Application\ModuleApi\DictionaryValueUsages;
use App\Modules\Catalogue\Application\Providers\CatalogueEventServiceProvider;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\EloquentDictionaryValueRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Support\DictionaryValueViewSections;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;

class CatalogueModuleServiceProvider extends ModuleServiceProvider
{
    /** Stable name stored in every entry: never rename it. */
    private const string AUDIT_KEY = 'catalogue.dictionary_value';

    protected bool $loadsTranslations = true;

    /** @var array<class-string> */
    protected array $eventProviders = [
        CatalogueEventServiceProvider::class,
    ];

    /** @var array<class-string, class-string> */
    protected array $containerBindings = [
        DictionaryValueRepository::class => EloquentDictionaryValueRepository::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(DictionaryValueUsages::class);
        $this->app->singleton(DictionaryValueViewSections::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->declarePermissions($this->app->make(RoleRegistry::class));
        $this->registerAuditableRecords($this->app->make(AuditableRecords::class));
    }

    /** D-42: Admin holds it; Super Admin holds every Permission by wildcard; a Seller (User) holds none. */
    private function declarePermissions(RoleRegistry $registry): void
    {
        $registry->declarePermissions(CataloguePermission::class);
        $registry->grant(UserRole::Admin, CataloguePermission::Manage);
    }

    private function registerAuditableRecords(AuditableRecords $records): void
    {
        $records->register(new AuditableRecordType(
            key: self::AUDIT_KEY,
            modelClass: DictionaryValueModel::class,
            name: 'catalogue::audit.dictionary_value.type',
            title: fn (DictionaryValueModel $value): string => $value->name,
            url: fn (DictionaryValueModel $value): string => DictionaryValueResource::getUrl('view', ['record' => $value]),
            excludedFields: ['name_key'],
            fieldLabels: [
                'dictionary' => 'catalogue::audit.dictionary_value.fields.dictionary',
                'name' => 'catalogue::audit.dictionary_value.fields.name',
                'withdrawn' => 'catalogue::audit.dictionary_value.fields.withdrawn',
            ],
            valuePresenters: [
                'dictionary' => ValuePresenter::enum(Dictionary::class),
                'withdrawn' => ValuePresenter::boolean(),
            ],
        ));
    }
}
