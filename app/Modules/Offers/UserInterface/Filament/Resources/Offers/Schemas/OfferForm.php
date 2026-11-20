<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Schemas;

use App\Modules\Catalogue\Application\ModuleApi\CatalogueDictionaries;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\PickGlassModelAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferFormData;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * Template B, layout (a): one column of sections, no side column (an Offer is a Draft here, and
 * its status is shown elsewhere). One form for sellers and admins (FR-01): "Szyba" on top, then
 * the piece, the private notes and the price. Every field may stay empty in a Draft (FR-06);
 * photos are added by a later ticket.
 */
class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::glassSection(),
            self::pieceSection(),
            self::notesSection(),
            self::priceSection(),
        ]);
    }

    /** FR-02: a Glass model (its card) or a hint, never both. */
    private static function glassSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.glass'))
            ->key('glass')
            ->headerActions([
                PickGlassModelAction::make(),
                Action::make('unknownGlassModel')
                    ->label(__('offers::resources.offer.actions.unknown_glass_model'))
                    ->color('gray')
                    ->visible(fn (Get $get): bool => ! self::hintIsShown($get))
                    ->action(function (Set $set): void {
                        $set('glass_model_id', null);
                        $set('unknown_model', true);
                    }),
            ])
            ->schema([
                Hidden::make('glass_model_id'),
                Hidden::make('unknown_model')->dehydrated(false),
                View::make('offers::filament.glass-model-summary')
                    ->viewData(fn (Get $get): array => [
                        'summary' => app(GlassModelSummaries::class)->of((int) $get('glass_model_id')),
                    ])
                    ->visible(fn (Get $get): bool => filled($get('glass_model_id'))),
                TextInput::make('identification_hint')
                    ->label(__('offers::resources.offer.fields.identification_hint'))
                    ->helperText(__('offers::resources.offer.helpers.identification_hint'))
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => self::hintIsShown($get)),
            ]);
    }

    private static function hintIsShown(Get $get): bool
    {
        return blank($get('glass_model_id')) && ($get('unknown_model') === true || filled($get('identification_hint')));
    }

    private static function pieceSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.piece'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                self::producerField(),
                self::yearField(),
                self::conditionField(),
                self::ratingField(),
                Textarea::make('description')
                    ->label(__('offers::resources.offer.fields.description'))
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    /** FR-03: the producer list is Catalogue's; a withdrawn producer the Offer already has stays on the list. */
    private static function producerField(): Select
    {
        return Select::make('producer_id')
            ->label(__('offers::resources.offer.fields.producer'))
            ->options(function (?OfferModel $record): array {
                $options = [];

                foreach (app(CatalogueDictionaries::class)->producers() as $producer) {
                    $options[$producer->id] = $producer->name;
                }

                if ($record?->producer_id !== null && ! isset($options[$record->producer_id])) {
                    $options += app(CatalogueDictionaries::class)->namesOf([$record->producer_id]);
                }

                return $options;
            })
            ->searchable()
            ->columnSpan(['default' => 'full', 'md' => 6])
            ->columnStart(['default' => 1]);
    }

    private static function yearField(): TextInput
    {
        return TextInput::make('year')
            ->label(__('offers::resources.offer.fields.year'))
            ->numeric()
            ->integer()
            ->minValue(1900)
            ->maxValue((int) date('Y') + 1)
            ->columnSpan(['default' => 'full', 'md' => 3])
            ->columnStart(['default' => 1]);
    }

    private static function conditionField(): Select
    {
        return Select::make('condition')
            ->label(__('offers::resources.offer.fields.condition'))
            ->options(OfferCondition::class)
            ->live()
            ->afterStateUpdated(function (mixed $state, Set $set): void {
                if (! OfferDomainPolicy::ratingApplies(OfferFormData::condition($state))) {
                    $set('rating', null);
                }
            })
            ->columnSpan(['default' => 'full', 'md' => 6])
            ->columnStart(['default' => 1]);
    }

    /** FR-04: appears only for a used or outlet piece. */
    private static function ratingField(): Select
    {
        return Select::make('rating')
            ->label(__('offers::resources.offer.fields.rating'))
            ->helperText(__('offers::resources.offer.helpers.rating'))
            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
            ->visible(fn (Get $get): bool => OfferDomainPolicy::ratingApplies(OfferFormData::condition($get('condition'))))
            ->columnSpan(['default' => 'full', 'md' => 3]);
    }

    /** The section's only field is named like the section, so its label is hidden. */
    private static function notesSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.notes'))
            ->description(__('offers::resources.offer.descriptions.notes'))
            ->schema([
                Textarea::make('private_notes')
                    ->label(__('offers::resources.offer.sections.notes'))
                    ->hiddenLabel()
                    ->rows(3),
            ]);
    }

    private static function priceSection(): Section
    {
        return Section::make(__('offers::resources.offer.sections.price'))
            ->schema([
                TextInput::make('price')
                    ->label(__('offers::resources.offer.fields.price'))
                    ->helperText(__('offers::resources.offer.helpers.price'))
                    ->numeric()
                    ->minValue(0)
                    ->suffix('zł'),
            ]);
    }
}
