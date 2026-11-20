<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Tables;

use App\Core\UserInterface\CurrentActor;
use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOffersBulkAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferWithGlassModelAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\RejectOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferGlassLabel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferRating;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Template A (list): list with the approval actions (FR-09, FR-11); filters and stat cards come later. The same table serves "Moje oferty" and "Wszystkie oferty". */
class OffersTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->recordUrl(fn (OfferModel $record): string => OfferResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    ApproveOfferAction::make(),
                    ApproveOfferWithGlassModelAction::make(),
                    RejectOfferAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ApproveOffersBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedTag,
            heading: __('offers::resources.offer.empty.heading'),
            description: __('offers::resources.offer.empty.description'),
            actions: [CreateAction::make()],
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (OfferModel $record): string => $record->number)
                ->subtitle(fn (OfferModel $record): ?string => OfferGlassLabel::of($record))
                ->status(fn (OfferModel $record) => $record->status)
                ->meta(fn (OfferModel $record): array => [$record->price_grosze === null ? null : number_format($record->price_grosze / 100, 2, ',', ' ').' zł']),

            TextColumn::make('number')
                ->label(__('offers::resources.offer.fields.number'))
                ->searchable()
                ->sortable()
                ->visibleFrom('md'),

            TextColumn::make('glass')
                ->label(__('offers::resources.offer.fields.glass'))
                ->state(fn (OfferModel $record): ?string => OfferGlassLabel::of($record))
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('producer.name')
                ->label(__('offers::resources.offer.fields.producer'))
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('condition')
                ->label(__('offers::resources.offer.fields.condition'))
                ->badge()
                ->color('gray')
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('rating')
                ->label(__('offers::resources.offer.fields.rating'))
                ->state(fn (OfferModel $record): ?string => OfferRating::stars($record))
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('price_grosze')
                ->label(__('offers::resources.offer.fields.price'))
                ->money('PLN', divideBy: 100)
                ->sortable()
                ->alignEnd()
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('owner.name')
                ->label(__('offers::resources.offer.fields.owner'))
                ->visible(fn (): bool => CurrentActor::can(OffersPermission::ManageAll))
                ->visibleFrom('md'),

            TextColumn::make('status')
                ->label(__('offers::resources.offer.fields.status'))
                ->badge()
                ->visibleFrom('md'),
        ];
    }
}
