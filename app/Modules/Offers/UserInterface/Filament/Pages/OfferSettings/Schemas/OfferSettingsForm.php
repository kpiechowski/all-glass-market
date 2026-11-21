<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Pages\OfferSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** One section; the page is layout (a). */
class OfferSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->statePath('data')
            ->components([
                Section::make(__('offers::pages.offer_settings.sections.prices'))
                    ->description(__('offers::pages.offer_settings.descriptions.prices'))
                    ->schema([
                        TextInput::make('vat_rate_percent')
                            ->label(__('offers::pages.offer_settings.fields.vat_rate'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->required(),
                    ]),
            ]);
    }
}
