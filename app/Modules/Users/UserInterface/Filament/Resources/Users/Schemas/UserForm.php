<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Domain\Enums\RoleEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
            Section::make(__('users::resource.sections.user_details'))
                ->aside()
                ->description(__('users::resource.sections.user_details_description'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('role')
                            ->label(__('users::resource.fields.role'))
                            ->options(RoleEnum::class)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label(__('users::resource.fields.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label(__('users::resource.fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('phone')
                            ->label(__('users::resource.fields.phone'))
                            ->tel()
                            ->maxLength(50)
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make(__('users::resource.sections.security'))
                ->aside()
                ->description(__('users::resource.sections.security_description'))
                ->schema([
                    TextInput::make('password')
                        ->label(__('users::resource.fields.password'))
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
                        ->label(__('users::resource.fields.password_confirmation'))
                        ->password()
                        ->revealable()
                        ->requiredWith('password')
                        ->hiddenOn('create')
                        ->same('password')
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),

            Section::make(__('users::resource.sections.company_details'))
                ->aside()
                ->description(__('users::resource.sections.company_details_description'))
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('company_account')
                            ->label(__('users::resource.fields.company_account'))
                            ->columnSpanFull(),

                        Toggle::make('has_accepted_terms')
                            ->label(__('users::resource.fields.has_accepted_terms'))
                            ->columnSpanFull(),

                        TextInput::make('company_name')
                            ->label(__('users::resource.fields.company_name'))
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('company_nip')
                            ->label(__('users::resource.fields.company_nip'))
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('company_address')
                            ->label(__('users::resource.fields.company_address'))
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('shipment_address')
                            ->label(__('users::resource.fields.shipment_address'))
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('city')
                            ->label(__('users::resource.fields.city'))
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('city_code')
                            ->label(__('users::resource.fields.city_code'))
                            ->maxLength(10)
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make(__('users::resource.sections.shipping_information'))
                ->aside()
                ->description(__('users::resource.sections.shipping_information_description'))
                ->schema([
                    RichEditor::make('shipping_information')
                        ->label(__('users::resource.fields.shipping_information'))
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
