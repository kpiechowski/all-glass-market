<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('users::profile.sections.personal_details'))
                    ->aside()
                    ->description(__('users::profile.sections.personal_details_description'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('users::profile.fields.name'))
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('email')
                                ->label(__('users::profile.fields.email'))
                                ->email()
                                ->required()
                                ->unique('users', 'email', ignorable: fn () => auth()->user())
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('phone')
                                ->label(__('users::profile.fields.phone'))
                                ->tel()
                                ->maxLength(50)
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make(__('users::profile.sections.company_account'))
                    ->aside()
                    ->description(__('users::profile.sections.company_account_description'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('company_name')
                                    ->label(__('users::profile.fields.company_name'))
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('company_nip')
                                    ->label(__('users::profile.fields.company_nip'))
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('company_address')
                                    ->label(__('users::profile.fields.company_address'))
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('shipment_address')
                                    ->label(__('users::profile.fields.shipment_address'))
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('city')
                                    ->label(__('users::profile.fields.city'))
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('city_code')
                                    ->label(__('users::profile.fields.city_code'))
                                    ->maxLength(10)
                                    ->columnSpan(1),
                            ]),

                    ]),
            ]);
    }
}
