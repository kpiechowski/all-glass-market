<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Details')
                ->aside()
                ->description('Basic account information')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50)
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make('Security')
                ->aside()
                ->description('Password management')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->minLength(8)
                        ->maxLength(255)
                        ->suffixAction(
                            Action::make('generate-password')
                                ->icon(Heroicon::ArrowPathRoundedSquare)
                                ->action(fn (Set $set) => $set('password', Str::password(12, true, true, true, false)))
                        )
                        ->columnSpanFull(),

                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->requiredWith('password')
                        ->hiddenOn('create')
                        ->same('password')
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Company Details')
                ->aside()
                ->description('Company information and billing address')
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('company_account')
                            ->label('Company account')
                            ->columnSpanFull(),

                        Toggle::make('has_accepted_terms')
                            ->label('Has accepted terms')
                            ->columnSpanFull(),

                        TextInput::make('company_name')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('company_nip')
                            ->label('NIP')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('company_address')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('shipment_address')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('city')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('city_code')
                            ->label('Postal code')
                            ->maxLength(10)
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make('Shipping Information')
                ->aside()
                ->description('Displayed in Otomoto offer descriptions for this user\'s offers. Leave empty to use the offer/category default.')
                ->schema([
                    RichEditor::make('shipping_information')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
