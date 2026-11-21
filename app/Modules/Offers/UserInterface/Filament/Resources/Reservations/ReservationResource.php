<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Reservations;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Offers\Infrastructure\Persistence\Queries\ReservationQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Reservations\Pages\ListReservations;
use App\Modules\Offers\UserInterface\Filament\Resources\Reservations\Tables\ReservationsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-32: "Rezerwacje", the current Reservations on the seller's own Offers with their authors (an admin sees all).
 * Read-only: a row opens the Offer, where the Reservation is released or sold. Listed in the "Oferty" group.
 */
class ReservationResource extends Resource
{
    protected static ?string $model = ReservationModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    public static function getModelLabel(): string
    {
        return __('offers::resources.reservation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offers::resources.reservation.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(ReservationQueries::class)->currentVisibleTo((int) auth()->id(), CurrentActor::can(OffersPermission::ManageAll));
    }

    public static function canViewAny(): bool
    {
        return CurrentActor::can(OffersPermission::Create);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
        ];
    }
}
