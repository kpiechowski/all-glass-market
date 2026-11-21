<?php

declare(strict_types=1);

namespace App\Modules\Offers;

use App\Core\Providers\ModuleServiceProvider;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Catalogue\Application\ModuleApi\DictionaryValueUsages;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelLinks;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelUsages;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Application\Providers\OfferJournalSubject;
use App\Modules\Offers\Application\Providers\OffersEventServiceProvider;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Domain\Repositories\OfferPhotoStorage;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use App\Modules\Offers\Domain\Repositories\SaleRepository;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Offers\Infrastructure\Persistence\EloquentInquiryRepository;
use App\Modules\Offers\Infrastructure\Persistence\EloquentOfferRepository;
use App\Modules\Offers\Infrastructure\Persistence\EloquentReservationRepository;
use App\Modules\Offers\Infrastructure\Persistence\EloquentSaleRepository;
use App\Modules\Offers\Infrastructure\Persistence\EloquentVatRateSetting;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Offers\Infrastructure\Persistence\OfferGlassModelUsage;
use App\Modules\Offers\Infrastructure\Persistence\OfferProducerUsage;
use App\Modules\Offers\Infrastructure\Persistence\StorageOfferPhotoStorage;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferGlassModelLinks;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferListGlass;
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
        ReservationRepository::class => EloquentReservationRepository::class,
        SaleRepository::class => EloquentSaleRepository::class,
        InquiryRepository::class => EloquentInquiryRepository::class,
        VatRateSetting::class => EloquentVatRateSetting::class,
        OfferPhotoStorage::class => StorageOfferPhotoStorage::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->scoped(BuyersPrices::class);
        $this->app->scoped(OfferListGlass::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerRoutes();
        $this->declarePermissions($this->app->make(RoleRegistry::class));
        $this->registerAuditableRecords($this->app->make(AuditableRecords::class));
        $this->registerCatalogueSeams();
        $this->app->make(JournalSubjects::class)->register(OwnerJournal::KIND, new OfferJournalSubject);
    }

    /** FR-25: Catalogue keeps knowing nothing of Offers; Offers tells it where its Glass models and producers are used, and what to link on a card. */
    private function registerCatalogueSeams(): void
    {
        $this->app->make(GlassModelUsages::class)->register(new OfferGlassModelUsage);
        $this->app->make(DictionaryValueUsages::class)->register(new OfferProducerUsage);
        $this->app->make(GlassModelLinks::class)->register($this->app->make(OfferGlassModelLinks::class));
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

    /** §9 Audit: the Offer, its Reservations, Sales and Inquiries are auditable (RULE-10). */
    private function registerAuditableRecords(AuditableRecords $records): void
    {
        $this->registerOfferRecord($records);
        $this->registerReservationRecord($records);
        $this->registerSaleRecord($records);
        $this->registerInquiryRecord($records);
    }

    private function registerOfferRecord(AuditableRecords $records): void
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

    private function registerReservationRecord(AuditableRecords $records): void
    {
        $records->register(new AuditableRecordType(
            key: ReservationModel::AUDIT_KEY,
            modelClass: ReservationModel::class,
            name: 'offers::audit.reservation.type',
            title: fn (ReservationModel $reservation): string => __('offers::audit.reservation.title', ['number' => $reservation->offer->number]),
            url: fn (ReservationModel $reservation): string => OfferResource::getUrl('view', ['record' => $reservation->offer_id]),
            excludedFields: ['is_current'],
            fieldLabels: [
                'offer_id' => 'offers::audit.reservation.fields.offer',
                'author_id' => 'offers::audit.reservation.fields.author',
                'made_by_admin' => 'offers::audit.reservation.fields.made_by_admin',
                'note' => 'offers::audit.reservation.fields.note',
                'made_at' => 'offers::audit.reservation.fields.made_at',
                'ended_as' => 'offers::audit.reservation.fields.ended_as',
                'ended_at' => 'offers::audit.reservation.fields.ended_at',
                'inquiry_id' => 'offers::audit.reservation.fields.inquiry',
            ],
            valuePresenters: [
                'offer_id' => ValuePresenter::reference(OfferModel::class, 'number'),
                'author_id' => ValuePresenter::reference(UserModel::class, 'name'),
                'made_by_admin' => ValuePresenter::boolean(),
                'made_at' => ValuePresenter::date(),
                'ended_as' => ValuePresenter::enum(ReservationEnd::class),
                'ended_at' => ValuePresenter::date(),
                'inquiry_id' => ValuePresenter::resolved(fn (mixed $id): ?string => is_numeric($id) ? '#'.$id : null),
            ],
        ));
    }

    private function registerSaleRecord(AuditableRecords $records): void
    {
        $records->register(new AuditableRecordType(
            key: SaleModel::AUDIT_KEY,
            modelClass: SaleModel::class,
            name: 'offers::audit.sale.type',
            title: fn (SaleModel $sale): string => __('offers::audit.sale.title', ['number' => $sale->offer->number]),
            url: fn (SaleModel $sale): string => OfferResource::getUrl('view', ['record' => $sale->offer_id]),
            fieldLabels: [
                'offer_id' => 'offers::audit.sale.fields.offer',
                'channel' => 'offers::audit.sale.fields.channel',
                'sale_price_grosze' => 'offers::audit.sale.fields.sale_price',
                'entered_price_grosze' => 'offers::audit.sale.fields.entered_price',
                'vat_rate_basis_points' => 'offers::audit.sale.fields.vat_rate',
                'buyers_price_grosze' => 'offers::audit.sale.fields.buyers_price',
                'marked_by_id' => 'offers::audit.sale.fields.marked_by',
                'sold_at' => 'offers::audit.sale.fields.sold_at',
                'returned_at' => 'offers::audit.sale.fields.returned_at',
                'return_reason' => 'offers::audit.sale.fields.return_reason',
                'returned_by_id' => 'offers::audit.sale.fields.returned_by',
            ],
            valuePresenters: [
                'offer_id' => ValuePresenter::reference(OfferModel::class, 'number'),
                'channel' => ValuePresenter::enum(SaleChannel::class),
                'sale_price_grosze' => ValuePresenter::moneyInGrosze(),
                'entered_price_grosze' => ValuePresenter::moneyInGrosze(),
                'vat_rate_basis_points' => ValuePresenter::resolved(fn (mixed $raw): ?string => is_numeric($raw) ? rtrim(rtrim(number_format((int) $raw / 100, 2, ',', ''), '0'), ',').' %' : null),
                'buyers_price_grosze' => ValuePresenter::moneyInGrosze(),
                'marked_by_id' => ValuePresenter::reference(UserModel::class, 'name'),
                'sold_at' => ValuePresenter::date(),
                'returned_at' => ValuePresenter::date(),
                'returned_by_id' => ValuePresenter::reference(UserModel::class, 'name'),
            ],
        ));
    }

    private function registerInquiryRecord(AuditableRecords $records): void
    {
        $records->register(new AuditableRecordType(
            key: InquiryModel::AUDIT_KEY,
            modelClass: InquiryModel::class,
            name: 'offers::audit.inquiry.type',
            title: fn (InquiryModel $inquiry): string => __('offers::audit.inquiry.title', ['number' => $inquiry->offer->number]),
            url: fn (InquiryModel $inquiry): string => OfferResource::getUrl('view', ['record' => $inquiry->offer_id]),
            fieldLabels: [
                'offer_id' => 'offers::audit.inquiry.fields.offer',
                'sender_id' => 'offers::audit.inquiry.fields.sender',
                'channel' => 'offers::audit.inquiry.fields.channel',
                'note' => 'offers::audit.inquiry.fields.note',
                'state' => 'offers::audit.inquiry.fields.state',
                'sent_at' => 'offers::audit.inquiry.fields.sent_at',
                'read_at' => 'offers::audit.inquiry.fields.read_at',
                'answered_at' => 'offers::audit.inquiry.fields.answered_at',
            ],
            valuePresenters: [
                'offer_id' => ValuePresenter::reference(OfferModel::class, 'number'),
                'sender_id' => ValuePresenter::reference(UserModel::class, 'name'),
                'channel' => ValuePresenter::enum(SaleChannel::class),
                'state' => ValuePresenter::enum(InquiryState::class),
                'sent_at' => ValuePresenter::date(),
                'read_at' => ValuePresenter::date(),
                'answered_at' => ValuePresenter::date(),
            ],
        ));
    }
}
