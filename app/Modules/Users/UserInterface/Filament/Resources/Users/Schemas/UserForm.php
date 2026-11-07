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

/**
 * Template B, layout (a): one column of sections. Status, role and dates are read on the record
 * view; the edit form carries only inputs, so no side column. On edit the password is an action
 * in the page's "⋮" (SetUserPasswordAction), so the password section exists only on create.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::accountSection(),
            self::passwordSection(),
            self::extraPermissionsSection(),
            self::companySection(),
            self::pickupAddressSection(),
            self::shippingSection(),
        ]);
    }

    private static function accountSection(): Section
    {
        return Section::make(__('users::resources.user.sections.account_details'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                // FR-24/25: never Super Admin; hidden when editing oneself or a Super Admin (RULE-08).
                Select::make('role')
                    ->label(__('users::resources.user.fields.role'))
                    ->options(fn (): array => RoleDisplay::assignableOptions())
                    ->required()
                    ->visible(fn (?UserModel $record): bool => $record === null
                        || UserManagementPolicy::canChangeRoleOf((int) auth()->id(), (int) $record->id, (string) $record->role))
                    ->columnSpan(['default' => 'full', 'md' => 6])
                    ->columnStart(['default' => 1]),

                TextInput::make('name')
                    ->label(__('users::resources.user.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 8])
                    ->columnStart(['default' => 1]),

                TextInput::make('email')
                    ->label(__('users::resources.user.fields.email'))
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 8])
                    ->columnStart(['default' => 1]),

                TextInput::make('phone')
                    ->label(__('users::resources.user.fields.phone'))
                    ->tel()
                    ->maxLength(50)
                    ->columnSpan(['default' => 'full', 'md' => 4]),
            ]);
    }

    /** FR-06, RULE-11: only a Super Admin sees this part of the form; the handler checks again. */
    private static function extraPermissionsSection(): Section
    {
        return Section::make(__('users::resources.user.sections.extra_permissions'))
            ->description(__('users::resources.user.descriptions.extra_permissions'))
            ->visible(fn (): bool => RoleDisplay::actorSeesExtraPermissions())
            ->schema([
                CheckboxList::make('extra_permissions')
                    ->label(__('users::resources.user.sections.extra_permissions'))
                    ->hiddenLabel()
                    ->options(fn (): array => RoleDisplay::permissionOptions())
                    ->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    private static function companySection(): Section
    {
        return Section::make(__('users::resources.user.sections.company_details'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('company_name')
                    ->label(__('users::resources.user.fields.company_name'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('company_nip')
                    ->label(__('users::resources.user.fields.company_nip'))
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 4]),
                TextInput::make('company_address')
                    ->label(__('users::resources.user.fields.company_address'))
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    /** One address, one section: street, then postcode + city. */
    private static function pickupAddressSection(): Section
    {
        return Section::make(__('users::resources.user.sections.pickup_address'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('shipment_address')
                    ->label(__('users::resources.user.fields.street'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('city_code')
                    ->label(__('users::resources.user.fields.postal_code'))
                    ->placeholder('00-000')
                    ->maxLength(10)
                    ->columnSpan(['default' => 'full', 'md' => 3]),
                TextInput::make('city')
                    ->label(__('users::resources.user.fields.city'))
                    ->maxLength(255)
                    ->columnSpan(['default' => 'full', 'md' => 9]),
            ]);
    }

    /** The section's only field is named like the section, so its label is hidden. */
    private static function shippingSection(): Section
    {
        return Section::make(__('users::resources.user.sections.shipping'))
            ->description(__('users::resources.user.descriptions.shipping'))
            ->schema([
                RichEditor::make('shipping_note')
                    ->label(__('users::resources.user.sections.shipping'))
                    ->hiddenLabel(),
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
            ->visible(fn (): bool => app(EmailSending::class)->isAvailable());
    }

    /** True when the temporary password fields apply: no invitation is sent. */
    private static function asksForPassword(Get $get): bool
    {
        return ($get('password_method') ?? 'temporary') === 'temporary';
    }

    /** Create only (WF-04): an invitation, or a temporary password the User changes after signing in. */
    private static function passwordSection(): Section
    {
        return Section::make(__('users::resources.user.sections.password'))
            ->visible(fn (string $operation): bool => $operation === 'create')
            ->schema([
                self::passwordMethodField(),

                TextInput::make('password')
                    ->label(__('users::resources.user.fields.password'))
                    ->helperText(__('users::resources.user.helpers.password_create'))
                    ->password()
                    ->revealable()
                    ->visible(fn (Get $get): bool => self::asksForPassword($get))
                    ->required(fn (Get $get): bool => self::asksForPassword($get))
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
                    ->visible(fn (Get $get): bool => self::asksForPassword($get))
                    ->requiredWith('password')
                    ->same('password')
                    ->dehydrated(false),
            ]);
    }
}
