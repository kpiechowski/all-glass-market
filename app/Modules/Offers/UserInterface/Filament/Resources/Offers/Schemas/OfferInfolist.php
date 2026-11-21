<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Schemas;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\Application\ModuleApi\RecordChangesAfter;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferRating;
use App\Modules\Offers\UserInterface\Http\Controllers\PrivatePhotoController;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Template C, layout (a): stacked sheet sections. The Glass model shows Catalogue's current data
 * (FR-15), withdrawn models included; the private notes only to the owner and admins (FR-05).
 */
class OfferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::changesSinceApprovalSection(),
            self::offerSection(),
            self::glassSection(),
            self::pieceSection(),
            self::photosSection(),
            self::notesSection(),
            self::privatePhotosSection(),
        ]);
    }

    /**
     * FR-12: on a Pending Offer approved before, what changed since that approval, read from Audit's
     * Change history (the approval's own change is left out). Admins only.
     */
    private static function changesSinceApprovalSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.changes_since_approval'))
            ->visible(fn (OfferModel $record): bool => CurrentActor::can(OffersPermission::ManageAll)
                && $record->status === OfferStatus::Pending
                && $record->last_approved_at !== null)
            ->schema([
                View::make('offers::filament.changes-since-approval')
                    ->viewData(fn (OfferModel $record): array => [
                        'lines' => app(RecordChangesAfter::class)->lastChangeOf(OfferModel::AUDIT_KEY, $record->id, 'last_approved_at', ['status']) ?? [],
                    ]),
            ]);
    }

    private static function offerSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.offer'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('number')->label(__('offers::resources.offer.fields.number'))->copyable(),
                TextEntry::make('status')->label(__('offers::resources.offer.fields.status'))->badge(),
                TextEntry::make('owner.name')->label(__('offers::resources.offer.fields.owner')),
                TextEntry::make('price_grosze')
                    ->label(__('offers::resources.offer.fields.price'))
                    ->money('PLN', divideBy: 100)
                    ->placeholder('—'),
                TextEntry::make('buyers_price')
                    ->label(__('offers::resources.offer.fields.buyers_price'))
                    ->state(fn (OfferModel $record): ?int => app(BuyersPrices::class)->forOffer($record->owner_id, $record->price_grosze))
                    ->money('PLN', divideBy: 100)
                    ->placeholder('—'),
                TextEntry::make('created_at')->label(__('offers::resources.offer.fields.created_at'))->date('d.m.Y'),
                TextEntry::make('first_approved_at')
                    ->label(__('offers::resources.offer.fields.first_approved_at'))
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->visible(fn (): bool => CurrentActor::can(OffersPermission::ManageAll)),
                TextEntry::make('trashed_at')
                    ->label(__('offers::resources.offer.fields.trashed_at'))
                    ->date('d.m.Y')
                    ->visible(fn (OfferModel $record): bool => $record->trashed_at !== null && CurrentActor::can(OffersPermission::ManageAll)),
                TextEntry::make('last_approved_at')
                    ->label(__('offers::resources.offer.fields.last_approved_at'))
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->visible(fn (): bool => CurrentActor::can(OffersPermission::ManageAll)),
            ]);
    }

    private static function glassSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.glass'))
            ->schema([
                View::make('offers::filament.glass-model-summary')
                    ->viewData(fn (OfferModel $record): array => [
                        'summary' => app(GlassModelSummaries::class)->of((int) $record->glass_model_id),
                    ])
                    ->visible(fn (OfferModel $record): bool => $record->glass_model_id !== null),
                TextEntry::make('identification_hint')
                    ->label(__('offers::resources.offer.fields.identification_hint'))
                    ->visible(fn (OfferModel $record): bool => $record->glass_model_id === null)
                    ->placeholder('—'),
            ]);
    }

    private static function pieceSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.piece'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('producer.name')->label(__('offers::resources.offer.fields.producer'))->placeholder('—'),
                TextEntry::make('year')->label(__('offers::resources.offer.fields.year'))->placeholder('—'),
                TextEntry::make('condition')->label(__('offers::resources.offer.fields.condition'))->badge()->color('gray')->placeholder('—'),
                TextEntry::make('rating')
                    ->label(__('offers::resources.offer.fields.rating'))
                    ->state(fn (OfferModel $record): ?string => OfferRating::stars($record))
                    ->placeholder('—'),
                TextEntry::make('description')->label(__('offers::resources.offer.fields.description'))->placeholder('—'),
            ]);
    }

    private static function photosSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.photos'))
            ->schema([
                View::make('offers::filament.photo-gallery')
                    ->viewData(fn (OfferModel $record): array => [
                        'urls' => array_map(fn (string $path): string => Storage::disk('public')->url($path), $record->public_photos ?? []),
                        'marksMain' => true,
                    ]),
            ]);
    }

    /** FR-05: the owner and admins only; each photo comes through the guarded route, never by its own URL. */
    private static function privatePhotosSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.private_photos'))
            ->description(__('offers::resources.offer.descriptions.private_photos'))
            ->visible(fn (OfferModel $record): bool => self::ownerOrAdmin($record))
            ->schema([
                View::make('offers::filament.photo-gallery')
                    ->viewData(fn (OfferModel $record): array => [
                        'urls' => array_map(
                            fn (int $position): string => route(PrivatePhotoController::ROUTE, ['offer' => $record->id, 'position' => $position]),
                            array_keys($record->private_photos ?? []),
                        ),
                        'marksMain' => false,
                    ]),
            ]);
    }

    private static function ownerOrAdmin(OfferModel $record): bool
    {
        return OfferDomainPolicy::canBeSeenBy($record->owner_id, (int) auth()->id(), CurrentActor::can(OffersPermission::ManageAll));
    }

    private static function notesSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.notes'))
            ->description(__('offers::resources.offer.descriptions.notes'))
            ->visible(fn (OfferModel $record): bool => self::ownerOrAdmin($record))
            ->schema([
                TextEntry::make('private_notes')
                    ->label(__('offers::resources.offer.sections.notes'))
                    ->hiddenLabel()
                    ->placeholder('—'),
            ]);
    }
}
