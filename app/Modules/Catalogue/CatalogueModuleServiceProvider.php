<?php

declare(strict_types=1);

namespace App\Modules\Catalogue;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Catalogue\Application\ModuleApi\DictionaryValueUsages;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelThumbnails;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelUsages;
use App\Modules\Catalogue\Application\Providers\CatalogueEventServiceProvider;
use App\Modules\Catalogue\Domain\Contracts\CandidateSearch;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\Repositories\ThumbnailMappingRepository;
use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\EloquentCandidateSearch;
use App\Modules\Catalogue\Infrastructure\Persistence\EloquentDictionaryValueRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\EloquentGlassModelRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\EloquentThumbnailMappingRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\EloquentVehicleRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\GlassModelDictionaryValueUsage;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\Infrastructure\Persistence\VehicleDictionaryUsage;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Support\DictionaryValueViewSections;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Support\GlassModelViewSections;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;

class CatalogueModuleServiceProvider extends ModuleServiceProvider
{
    /** Stable name stored in every entry: never rename it. */
    private const string AUDIT_KEY = 'catalogue.dictionary_value';

    private const string VEHICLE_AUDIT_KEY = 'catalogue.vehicle';

    protected bool $loadsTranslations = true;

    /** @var array<class-string> */
    protected array $eventProviders = [
        CatalogueEventServiceProvider::class,
    ];

    /** @var array<class-string, class-string> */
    protected array $containerBindings = [
        DictionaryValueRepository::class => EloquentDictionaryValueRepository::class,
        VehicleRepository::class => EloquentVehicleRepository::class,
        GlassModelRepository::class => EloquentGlassModelRepository::class,
        CandidateSearch::class => EloquentCandidateSearch::class,
        ThumbnailMappingRepository::class => EloquentThumbnailMappingRepository::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(DictionaryValueUsages::class);
        $this->app->singleton(DictionaryValueViewSections::class);
        $this->app->scoped(GlassModelThumbnails::class);
        $this->app->singleton(GlassModelUsages::class);
        $this->app->singleton(GlassModelViewSections::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->declarePermissions($this->app->make(RoleRegistry::class));
        $this->registerAuditableRecords($this->app->make(AuditableRecords::class));
        $this->registerVehicleAuditableRecord($this->app->make(AuditableRecords::class));
        $this->registerDictionaryUsages($this->app->make(DictionaryValueUsages::class));
    }

    /** FR-24: a make or body a vehicle has, or a position, colour or equipment a Glass model has, cannot be deleted. */
    private function registerDictionaryUsages(DictionaryValueUsages $usages): void
    {
        $usages->register(new VehicleDictionaryUsage);
        $usages->register(new GlassModelDictionaryValueUsage);
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

    private function registerVehicleAuditableRecord(AuditableRecords $records): void
    {
        $records->register(new AuditableRecordType(
            key: self::VEHICLE_AUDIT_KEY,
            modelClass: VehicleModel::class,
            name: 'catalogue::audit.vehicle.type',
            title: fn (VehicleModel $vehicle): string => $vehicle->name,
            url: fn (VehicleModel $vehicle): string => VehicleResource::getUrl('view', ['record' => $vehicle]),
            excludedFields: ['name_key'],
            fieldLabels: [
                'name' => 'catalogue::audit.vehicle.fields.name',
                'make_id' => 'catalogue::audit.vehicle.fields.make',
                'model' => 'catalogue::audit.vehicle.fields.model',
                'body_id' => 'catalogue::audit.vehicle.fields.body',
                'year_from' => 'catalogue::audit.vehicle.fields.year_from',
                'year_to' => 'catalogue::audit.vehicle.fields.year_to',
                'generation' => 'catalogue::audit.vehicle.fields.generation',
                'argic_code' => 'catalogue::audit.vehicle.fields.argic_code',
            ],
            valuePresenters: [
                'make_id' => ValuePresenter::reference(DictionaryValueModel::class, 'name'),
                'body_id' => ValuePresenter::reference(DictionaryValueModel::class, 'name'),
            ],
        ));
    }
}
