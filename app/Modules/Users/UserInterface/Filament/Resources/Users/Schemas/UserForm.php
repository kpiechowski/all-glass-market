<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Application\ModuleApi\EmailSending;
use App\Modules\Users\Domain\Policies\UserManagementPolicy;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
            self::accountSection(),
            self::extraPermissionsSection(),
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

    /** FR-06, RULE-11: only a Super Admin sees this part of the form; the handler checks again. */
    private static function extraPermissionsSection(): Section
    {
        return Section::make(__('users::resources.user.sections.extra_permissions'))
            ->icon(Heroicon::Key)
            ->visible(fn (): bool => RoleDisplay::actorSeesExtraPermissions())
            ->schema([
                CheckboxList::make('extra_permissions')
                    ->label(__('users::resources.user.fields.extra_permissions'))
                    ->helperText(__('users::resources.user.helpers.extra_permissions'))
                    ->options(fn (): array => RoleDisplay::permissionOptions())
                    ->columns(2),
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

    /** FR-24: an invitation is offered only while e-mail sending works (spec §10). */
    private static function passwordMethodField(): Radio
    {
        return Radio::make('password_method')
            ->label(__('users::resources.user.fields.password_method'))
            ->options([
                'invitation' => __('users::resources.user.password_methods.invitation'),
                'temporary' => __('users::resources.user.password_methods.temporary'),
            ])
            ->default(fn (): string => app(EmailSending::class)->isAvailable() ? 'invitation' : 'temporary')
            ->live()
            ->visible(fn (string $operation): bool => $operation === 'create' && app(EmailSending::class)->isAvailable())
            ->dehydrated(fn (string $operation): bool => $operation === 'create');
    }

    /** True when the password fields apply: always on edit, on create unless an invitation is sent. */
    private static function asksForPassword(string $operation, Get $get): bool
    {
        return $operation !== 'create' || ($get('password_method') ?? 'temporary') === 'temporary';
    }

    /** Invitation or temporary password on create (WF-04); on edit an optional, collapsed change (FR-25). */
    private static function passwordSection(): Section
    {
        return Section::make(__('users::resources.user.sections.password'))
            ->icon(Heroicon::LockClosed)
            ->collapsed(fn (string $operation): bool => $operation === 'edit')
            ->columns(1)
            ->schema([
                self::passwordMethodField(),

                TextInput::make('password')
                    ->label(fn (string $operation): string => $operation === 'create'
                        ? __('users::resources.user.fields.password')
                        : __('users::resources.user.fields.password_edit'))
                    ->helperText(fn (string $operation): string => $operation === 'create'
                        ? __('users::resources.user.helpers.password_create')
                        : __('users::resources.user.helpers.password_edit'))
                    ->password()
                    ->revealable()
                    ->visible(fn (string $operation, Get $get): bool => self::asksForPassword($operation, $get))
                    ->required(fn (string $operation, Get $get): bool => $operation === 'create' && self::asksForPassword($operation, $get))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->maxLength(255)
                    ->copyable()
                    ->suffixAction(
                        Action::make('generate-password')
                            ->label(__('users::resources.user.actions.generate_password'))
                            ->icon(Heroicon::ArrowPathRoundedSquare)
                            ->action(fn (Set $set) => $set('password', Str::password(12, true, true, true, false)))
                    ),

                TextInput::make('password_confirmation')
                    ->label(__('users::resources.user.fields.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->visible(fn (string $operation, Get $get): bool => self::asksForPassword($operation, $get))
                    ->requiredWith('password')
                    ->same('password')
                    ->dehydrated(false),
            ]);
    }
}
