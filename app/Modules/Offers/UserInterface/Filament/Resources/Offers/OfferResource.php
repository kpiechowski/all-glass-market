<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\CreateOffer;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\EditOffer;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ListOffers;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Schemas\OfferForm;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Schemas\OfferInfolist;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Tables\OffersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Offers (FR-01 to FR-06 Draft saving, RULE-11). Writes are commands from the create and edit
 * pages. A seller's queries start from their own Offers only, so another seller's Offer is
 * not found on the list, the view or the edit page; whoever manages all Offers sees every one.
 * Listed in the "Oferty" group of `PanelNavigation`, as "Moje oferty" or "Wszystkie oferty".
 */
class OfferResource extends Resource
{
    protected static ?string $model = OfferModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return __('offers::resources.offer.label');
    }

    public static function getPluralModelLabel(): string
    {
        return self::managesAll() ? __('offers::resources.offer.all') : __('offers::resources.offer.mine');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(OfferQueries::class)->visibleTo((int) auth()->id(), self::managesAll());
    }

    public static function canViewAny(): bool
    {
        return CurrentActor::can(OffersPermission::Create);
    }

    public static function canCreate(): bool
    {
        return CurrentActor::can(OffersPermission::Create);
    }

    public static function canView(Model $record): bool
    {
        return self::canViewAny() && self::isVisibleTo($record);
    }

    public static function canEdit(Model $record): bool
    {
        return self::canViewAny() && self::isVisibleTo($record);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return OfferForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OfferInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OffersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffers::route('/'),
            'create' => CreateOffer::route('/create'),
            'view' => ViewOffer::route('/{record}'),
            'edit' => EditOffer::route('/{record}/edit'),
        ];
    }

    private static function managesAll(): bool
    {
        return CurrentActor::can(OffersPermission::ManageAll);
    }

    private static function isVisibleTo(Model $record): bool
    {
        return OfferDomainPolicy::canBeSeenBy((int) $record->getAttribute('owner_id'), (int) auth()->id(), self::managesAll());
    }
}
