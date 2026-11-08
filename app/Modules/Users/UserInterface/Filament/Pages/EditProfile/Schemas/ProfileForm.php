<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProfileForm
{
    /** Template D, layout (a): one column of sections, one form, one sticky Zapisz. */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(1)
            ->components([
                self::personalSection(),
                self::companySection(),
                self::shipmentAddressSection(),
                self::accountTypeSection(),
            ]);
    }

    private static function personalSection(): Section
    {
        return Section::make(__('users::profile.sections.personal_details'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('name')
                    ->label(__('users::profile.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 8])
                    ->columnStart(['default' => 1]),

                TextInput::make('email')
                    ->label(__('users::profile.fields.email'))
                    ->email()
                    ->required()
                    ->unique('users', 'email', ignorable: fn () => auth()->user())
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 8])
                    ->columnStart(['default' => 1]),

                TextInput::make('phone')
                    ->label(__('users::profile.fields.phone'))
                    ->tel()
                    ->required()
                    ->maxLength(50)
                    ->columnSpan(['default' => 'full', 'md' => 4]),
            ]);
    }

    private static function companySection(): Section
    {
        return Section::make(__('users::profile.sections.company'))
            ->description(__('users::profile.sections.company_description'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('company_name')
                    ->label(__('users::profile.fields.company_name'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('company_nip')
                    ->label(__('users::profile.fields.company_nip'))
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 4]),

                TextInput::make('company_address')
                    ->label(__('users::profile.fields.company_address'))
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    /** One address, one section: street, then postcode + city (FR-14: "Jeżeli inny niż adres firmy"). */
    private static function shipmentAddressSection(): Section
    {
        return Section::make(__('users::profile.sections.shipment_address'))
            ->description(__('users::profile.sections.shipment_address_description'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('shipment_address')
                    ->label(__('users::profile.fields.street'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('city_code')
                    ->label(__('users::profile.fields.city_code'))
                    ->placeholder('00-000')
                    ->maxLength(10)
                    ->columnSpan(['default' => 'full', 'md' => 3]),

                TextInput::make('city')
                    ->label(__('users::profile.fields.city'))
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 9]),
            ]);
    }

    /** FR-17..FR-19: the lock is read from the signed-in User's model. */
    private static function accountTypeSection(): Section
    {
        return Section::make(__('users::profile.sections.account_type'))
            ->description(__('users::profile.sections.account_type_description'))
            ->schema([
                // The section's only input is named like the section, so its label is hidden.
                Radio::make('account_type')
                    ->label(__('users::profile.fields.account_type'))
                    ->hiddenLabel()
                    ->options(AccountType::class)
                    ->required()
                    ->live()
                    ->disabled(fn (): bool => self::lockedUntil() !== null)
                    ->dehydrated(fn (): bool => self::lockedUntil() === null)
                    ->helperText(fn (): ?string => self::lockedUntil() === null
                        ? null
                        : __('users::profile.account_type_locked', ['time' => self::lockedUntil()->setTimezone('Europe/Warsaw')->format('H:i')]))
                    ->afterStateUpdated(function (mixed $state, EditProfile $livewire): void {
                        if (self::stateValue($state) === AccountType::Private->value) {
                            $livewire->mountAction('privateAccountNotice');
                        }
                    }),

                Text::make(__('users::profile.private_notice'))
                    ->visible(fn (Get $get): bool => self::stateValue($get('account_type')) !== AccountType::Company->value),

                Checkbox::make('seller_declaration_accepted')
                    ->label(__('users::profile.seller_declaration'))
                    ->visible(fn (Get $get): bool => self::stateValue($get('account_type')) === AccountType::Company->value)
                    ->accepted(fn (Get $get): bool => self::stateValue($get('account_type')) === AccountType::Company->value)
                    ->validationMessages(['accepted' => __('users::exceptions.declaration_not_accepted')])
                    ->dehydrated(),
            ]);
    }

    /** Until when the Account type is locked, or null when it is free to change. */
    public static function lockedUntil(): ?CarbonImmutable
    {
        /** @var UserModel|null $user */
        $user = auth()->user();
        $changedAt = $user?->account_type_changed_at;

        if ($changedAt === null) {
            return null;
        }

        $until = CarbonImmutable::instance($changedAt)->addMinutes(30);

        return $until->isFuture() ? $until : null;
    }

    private static function stateValue(mixed $state): ?string
    {
        return $state instanceof AccountType ? $state->value : ($state === null ? null : (string) $state);
    }
}
