<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Domain\Enums\RoleEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->contained(false)
                ->columnSpanFull()
                ->tabs([
                    Tab::make(__('users::resource.tabs.general'))
                        ->icon(Heroicon::UserCircle)
                        ->schema(self::generalTab()),

                    Tab::make(__('users::resource.tabs.password'))
                        ->icon(Heroicon::LockClosed)
                        ->schema(self::passwordTab()),
                ]),
        ]);
    }

    /** @return array<mixed> */
    private static function generalTab(): array
    {
        return [
            Section::make(__('users::resource.sections.account_details'))
                ->contained(false)
                ->icon(Heroicon::UserCircle)
                ->columns(2)
                ->schema([
                    Select::make('role')
                        ->label(__('users::resource.fields.role'))
                        ->options(RoleEnum::class)
                        ->required()
                        ->columnSpan(1),

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

            Section::make(__('users::resource.sections.company_account'))
                ->contained(false)
                ->icon(Heroicon::BuildingOffice)
                ->columns(1)
                ->schema([
                    Toggle::make('company_account')
                        ->label(__('users::resource.fields.company_account'))
                        ->inlineLabel()
                        ->live(),

                    Toggle::make('has_accepted_terms')
                        ->label(__('users::resource.fields.has_accepted_terms'))
                        ->inlineLabel()
                        ->visible(fn (Get $get): bool => (bool) $get('company_account')),
                ]),

            Section::make(__('users::resource.sections.company_details'))
                ->contained(false)
                ->icon(Heroicon::BuildingOffice2)
                ->columns(2)
                ->visible(fn (Get $get): bool => (bool) $get('company_account'))
                ->schema([
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

                    RichEditor::make('shipping_note')
                        ->label(__('users::resource.fields.shipping_note'))
                        ->columnSpanFull(),
                ]),
        ];
    }

    /** @return array<mixed> */
    private static function passwordTab(): array
    {
        return [
            Section::make(__('users::resource.sections.security'))
                ->contained(false)
                ->icon(Heroicon::LockClosed)
                ->columns(1)
                ->schema([
                    TextInput::make('password')
                        ->label(__('users::resource.fields.password'))
                        ->password()
                        ->revealable()
                        ->inlineLabel()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->minLength(8)
                        ->maxLength(255)
                        ->suffixAction(
                            Action::make('generate-password')
                                ->icon(Heroicon::ArrowPathRoundedSquare)
                                ->action(fn (Set $set) => $set('password', Str::password(12, true, true, true, false)))
                        ),

                    TextInput::make('password_confirmation')
                        ->label(__('users::resource.fields.password_confirmation'))
                        ->password()
                        ->revealable()
                        ->inlineLabel()
                        ->requiredWith('password')
                        ->hiddenOn('create')
                        ->same('password')
                        ->dehydrated(false),
                ]),
        ];
    }
}
