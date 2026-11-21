<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Reservations\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferGlassLabel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Template A (list): the row opens the Offer; nothing is changed from here. */
class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->recordUrl(fn (ReservationModel $record): string => OfferResource::getUrl('view', ['record' => $record->offer_id]))
            ->defaultSort('made_at', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedBookmark,
            heading: __('offers::resources.reservation.empty.heading'),
            description: __('offers::resources.reservation.empty.description'),
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (ReservationModel $record): string => $record->offer->number)
                ->subtitle(fn (ReservationModel $record): string => $record->note)
                ->flags(fn (ReservationModel $record): array => $record->made_by_admin ? [['label' => __('offers::resources.reservation.fields.locked'), 'icon' => Heroicon::OutlinedLockClosed]] : [])
                ->meta(fn (ReservationModel $record): array => [$record->author->name, $record->made_at->format('d.m.Y H:i')]),

            TextColumn::make('offer.number')
                ->label(__('offers::resources.reservation.fields.offer'))
                ->description(fn (ReservationModel $record): ?string => OfferGlassLabel::of($record->offer))
                ->sortable()
                ->visibleFrom('md'),

            TextColumn::make('note')
                ->label(__('offers::resources.reservation.fields.note'))
                ->limit(80)
                ->visibleFrom('md'),

            TextColumn::make('author.name')
                ->label(__('offers::resources.reservation.fields.author'))
                ->description(fn (ReservationModel $record): ?string => $record->made_by_admin ? __('offers::resources.reservation.fields.locked') : null)
                ->visibleFrom('md'),

            TextColumn::make('made_at')
                ->label(__('offers::resources.reservation.fields.made_at'))
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->visibleFrom('md'),
        ];
    }
}
