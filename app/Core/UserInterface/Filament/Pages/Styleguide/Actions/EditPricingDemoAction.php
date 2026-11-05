<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

/** Template E, longer task beside a list: six fields in a slide-over. Demo only, sends a notification. */
final class EditPricingDemoAction
{
    public static function make(string $name = 'editPricing'): Action
    {
        return Action::make($name)
            ->label('Edytuj cennik')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->color('gray')
            ->slideOver()
            ->modalHeading('Edytuj cennik')
            ->modalDescription('Lista pod spodem pozostaje widoczna.')
            ->modalSubmitActionLabel('Zapisz cennik')
            ->fillForm([
                'price_net' => '1250.00',
                'vat_rate' => '23',
                'discount' => '5',
                'valid_from' => now()->format('Y-m-d'),
                'shows_gross' => true,
                'note' => null,
            ])
            ->schema([
                TextInput::make('price_net')->label('Cena netto')->numeric()->suffix('zł')->required(),
                Select::make('vat_rate')->label('Stawka VAT')->options(['23' => '23%', '8' => '8%', '0' => '0%'])->required(),
                TextInput::make('discount')->label('Rabat')->numeric()->suffix('%'),
                DatePicker::make('valid_from')->label('Obowiązuje od')->required(),
                Toggle::make('shows_gross')->label('Pokazuj cenę brutto'),
                Textarea::make('note')->label('Uwagi')->rows(3),
            ])
            ->action(fn () => DemoNotification::success('Zapisano cennik'));
    }
}
