<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Schemas;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferRating;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * Template C, layout (a): stacked sheet sections. The Glass model shows Catalogue's current data
 * (FR-15), withdrawn models included; the private notes only to the owner and admins (FR-05).
 */
class OfferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::offerSection(),
            self::glassSection(),
            self::pieceSection(),
            self::notesSection(),
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
                TextEntry::make('created_at')->label(__('offers::resources.offer.fields.created_at'))->date('d.m.Y'),
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

    private static function notesSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.notes'))
            ->description(__('offers::resources.offer.descriptions.notes'))
            ->visible(fn (OfferModel $record): bool => OfferDomainPolicy::canBeSeenBy(
                $record->owner_id,
                (int) auth()->id(),
                CurrentActor::can(OffersPermission::ManageAll),
            ))
            ->schema([
                TextEntry::make('private_notes')
                    ->label(__('offers::resources.offer.sections.notes'))
                    ->hiddenLabel()
                    ->placeholder('—'),
            ]);
    }
}
