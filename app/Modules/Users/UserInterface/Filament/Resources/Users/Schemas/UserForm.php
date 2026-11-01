<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Domain\Policies\UserManagementPolicy;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
            self::accountSection(),
            self::companySection(),
            self::shippingSection(),
            self::passwordSection(),
        ]);
    }

    private static function accountSection(): Section
    {
        return Section::make(__('users::resources.user.sections.account_details'))
            ->icon(Heroicon::UserCircle)
            ->columns(2)
            ->schema([
                // FR-24/25: never Super Admin; hidden when editing oneself or a Super Admin (RULE-08).
                Select::make('role')
                    ->label(__('users::resources.user.fields.role'))
                    ->options(fn (): array => RoleDisplay::assignableOptions())
                    ->required()
                    ->visible(fn (?UserModel $record): bool => $record === null
                        || UserManagementPolicy::canChangeRoleOf((int) auth()->id(), (int) $record->id, (string) $record->role))
                    ->columnSpanFull(),

                TextInput::make('name')
                    ->label(__('users::resources.user.fields.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('users::resources.user.fields.email'))
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email', ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label(__('users::resources.user.fields.phone'))
                    ->tel()
                    ->maxLength(50),
            ]);
    }

    private static function companySection(): Section
    {
        return Section::make(__('users::resources.user.sections.company_details'))
            ->icon(Heroicon::BuildingOffice2)
            ->columns(2)
            ->schema([
                TextInput::make('company_name')->label(__('users::resources.user.fields.company_name'))->maxLength(255),
                TextInput::make('company_nip')->label(__('users::resources.user.fields.company_nip'))->maxLength(255),
                TextInput::make('company_address')->label(__('users::resources.user.fields.company_address'))->maxLength(255),
                TextInput::make('shipment_address')->label(__('users::resources.user.fields.pickup_address'))->maxLength(255),
                TextInput::make('city')->label(__('users::resources.user.fields.city'))->maxLength(255),
                TextInput::make('city_code')->label(__('users::resources.user.fields.postal_code'))->maxLength(10),
            ]);
    }

    private static function shippingSection(): Section
    {
        return Section::make(__('users::resources.user.sections.shipping'))
            ->icon(Heroicon::Truck)
            ->schema([
                RichEditor::make('shipping_note')
                    ->label(__('users::resources.user.fields.shipping_text'))
                    ->helperText(__('users::resources.user.helpers.shipping_text')),
            ]);
    }

    /** Temporary password on create (WF-04); on edit an optional, collapsed change (FR-25). */
    private static function passwordSection(): Section
    {
        return Section::make(__('users::resources.user.sections.password'))
            ->icon(Heroicon::LockClosed)
            ->collapsed(fn (string $operation): bool => $operation === 'edit')
            ->columns(1)
            ->schema([
                TextInput::make('password')
                    ->label(fn (string $operation): string => $operation === 'create'
                        ? __('users::resources.user.fields.password')
                        : __('users::resources.user.fields.password_edit'))
                    ->helperText(fn (string $operation): string => $operation === 'create'
                        ? __('users::resources.user.helpers.password_create')
                        : __('users::resources.user.helpers.password_edit'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->maxLength(255)
                    ->copyable()
                    ->suffixAction(
                        Action::make('generate-password')
                            ->icon(Heroicon::ArrowPathRoundedSquare)
                            ->action(fn (Set $set) => $set('password', Str::password(12, true, true, true, false)))
                    ),

                TextInput::make('password_confirmation')
                    ->label(__('users::resources.user.fields.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->requiredWith('password')
                    ->same('password')
                    ->dehydrated(false),
            ]);
    }
}
