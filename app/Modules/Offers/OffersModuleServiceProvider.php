<?php

declare(strict_types=1);

namespace App\Modules\Offers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Application\Providers\OfferJournalSubject;
use App\Modules\Offers\Application\Providers\OffersEventServiceProvider;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Repositories\OfferPhotoStorage;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Offers\Infrastructure\Persistence\EloquentOfferRepository;
use App\Modules\Offers\Infrastructure\Persistence\EloquentVatRateSetting;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\StorageOfferPhotoStorage;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Http\Controllers\PrivatePhotoController;
use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Support\Facades\Route;

class OffersModuleServiceProvider extends ModuleServiceProvider
{
    protected bool $loadsTranslations = true;

    /** @var array<class-string> */
    protected array $eventProviders = [
        OffersEventServiceProvider::class,
    ];

    /** @var array<class-string, class-string> */
    protected array $containerBindings = [
        OfferRepository::class => EloquentOfferRepository::class,
        VatRateSetting::class => EloquentVatRateSetting::class,
        OfferPhotoStorage::class => StorageOfferPhotoStorage::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->scoped(BuyersPrices::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerRoutes();
        $this->declarePermissions($this->app->make(RoleRegistry::class));
        $this->registerAuditableRecords($this->app->make(AuditableRecords::class));
        $this->app->make(JournalSubjects::class)->register(OwnerJournal::KIND, new OfferJournalSubject);
    }

    /** FR-05: the only way to a private photo is this route; it checks the viewer itself. */
    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->get('admin/offers/{offer}/private-photos/{position}', PrivatePhotoController::class)
            ->whereNumber(['offer', 'position'])
            ->name(PrivatePhotoController::ROUTE);
    }

    /** §3: a seller (User) works on own Offers; an admin on all; Super Admin holds every Permission by wildcard. */
    private function declarePermissions(RoleRegistry $registry): void
    {
        $registry->declarePermissions(OffersPermission::class);
        $registry->grant(UserRole::User, OffersPermission::Create);
        $registry->grant(UserRole::Admin, OffersPermission::Create, OffersPermission::ManageAll);
    }

    /** §9 Audit: the Offer is auditable. */
    private function registerAuditableRecords(AuditableRecords $records): void
    {
        $records->register(new AuditableRecordType(
            key: OfferModel::AUDIT_KEY,
            modelClass: OfferModel::class,
            name: 'offers::audit.offer.type',
            title: fn (OfferModel $offer): string => $offer->number,
            url: fn (OfferModel $offer): string => OfferResource::getUrl('view', ['record' => $offer]),
            fieldLabels: [
                'number' => 'offers::audit.offer.fields.number',
                'owner_id' => 'offers::audit.offer.fields.owner',
                'status' => 'offers::audit.offer.fields.status',
                'glass_model_id' => 'offers::audit.offer.fields.glass_model',
                'identification_hint' => 'offers::audit.offer.fields.identification_hint',
                'producer_id' => 'offers::audit.offer.fields.producer',
                'year' => 'offers::audit.offer.fields.year',
                'condition' => 'offers::audit.offer.fields.condition',
                'rating' => 'offers::audit.offer.fields.rating',
                'description' => 'offers::audit.offer.fields.description',
                'private_notes' => 'offers::audit.offer.fields.private_notes',
                'price_grosze' => 'offers::audit.offer.fields.price',
                'public_photos' => 'offers::audit.offer.fields.public_photos',
                'private_photos' => 'offers::audit.offer.fields.private_photos',
                'first_approved_at' => 'offers::audit.offer.fields.first_approved_at',
                'last_approved_at' => 'offers::audit.offer.fields.last_approved_at',
                'changed_since_approval' => 'offers::audit.offer.fields.changed_since_approval',
                'trashed_at' => 'offers::audit.offer.fields.trashed_at',
            ],
            valuePresenters: [
                'owner_id' => ValuePresenter::reference(UserModel::class, 'name'),
                'glass_model_id' => ValuePresenter::resolved(fn (mixed $id): ?string => is_numeric($id) ? app(GlassModelSummaries::class)->of((int) $id)?->mainEurocode : null),
                'status' => ValuePresenter::enum(OfferStatus::class),
                'producer_id' => ValuePresenter::reference(DictionaryValueModel::class, 'name'),
                'condition' => ValuePresenter::enum(OfferCondition::class),
                'price_grosze' => ValuePresenter::moneyInGrosze(),
                'public_photos' => ValuePresenter::fileCount(),
                'private_photos' => ValuePresenter::fileCount(),
                'first_approved_at' => ValuePresenter::date(),
                'last_approved_at' => ValuePresenter::date(),
                'changed_since_approval' => ValuePresenter::boolean(),
                'trashed_at' => ValuePresenter::date(),
            ],
        ));
    }
}
