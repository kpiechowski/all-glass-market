<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    /**
     * Seams: Offers' statistics (D-05) and the audit history add their sections here.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::accountSection(),
            self::extraPermissionsSection(),
            self::companySection(),
            self::shippingSection(),
        ]);
    }

    private static function accountSection(): Section
    {
        return Section::make(__('users::resources.user.sections.account_details'))
            ->icon(Heroicon::UserCircle)
            ->columns(2)
            ->schema([
                TextEntry::make('role')
                    ->label(__('users::resources.user.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => RoleDisplay::label($state))
                    ->color(fn (?string $state): string => RoleDisplay::color($state)),

                TextEntry::make('account_type')
                    ->label(__('users::resources.user.fields.account_type'))
                    ->badge(),

                TextEntry::make('deactivated_at')
                    ->label(__('users::resources.user.fields.status'))
                    ->badge()
                    ->color('danger')
                    ->visible(fn (UserModel $record): bool => ! $record->isActive())
                    ->state(fn (): string => __('users::resources.user.status.inactive')),

                TextEntry::make('name')->label(__('users::resources.user.fields.name')),

                TextEntry::make('email')
                    ->label(__('users::resources.user.fields.email'))
                    ->icon(Heroicon::Envelope)
                    ->copyable(),

                TextEntry::make('phone')
                    ->label(__('users::resources.user.fields.phone'))
                    ->icon(Heroicon::Phone)
                    ->placeholder('-'),

                TextEntry::make('created_at')
                    ->label(__('users::resources.user.fields.created_at'))
                    ->dateTime(),

                TextEntry::make('password_change_required')
                    ->label(__('users::resources.user.fields.password_change_required'))
                    ->badge()
                    ->color('warning')
                    ->state(fn (UserModel $record): ?string => $record->password_change_required
                        ? __('users::resources.user.markers.password_change_required')
                        : null)
                    ->placeholder('-'),
            ]);
    }

    /** FR-06: listed apart from the Role, for Super Admins only. */
    private static function extraPermissionsSection(): Section
    {
        return Section::make(__('users::resources.user.sections.extra_permissions'))
            ->icon(Heroicon::Key)
            ->visible(fn (): bool => RoleDisplay::actorSeesExtraPermissions())
            ->schema([
                TextEntry::make('extra_permissions')
                    ->label(__('users::resources.user.fields.extra_permissions'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RoleDisplay::permissionLabel($state))
                    ->placeholder(__('users::resources.user.no_extra_permissions')),
            ]);
    }

    private static function companySection(): Section
    {
        return Section::make(__('users::resources.user.sections.company_details'))
            ->icon(Heroicon::BuildingOffice2)
            ->columns(2)
            ->schema([
                TextEntry::make('company_name')->label(__('users::resources.user.fields.company_name'))->placeholder('-'),
                TextEntry::make('company_nip')->label(__('users::resources.user.fields.company_nip'))->placeholder('-'),
                TextEntry::make('company_address')->label(__('users::resources.user.fields.company_address'))->placeholder('-'),
                TextEntry::make('shipment_address')->label(__('users::resources.user.fields.pickup_address'))->placeholder('-'),
                TextEntry::make('city')->label(__('users::resources.user.fields.city'))->placeholder('-'),
                TextEntry::make('city_code')->label(__('users::resources.user.fields.postal_code'))->placeholder('-'),
            ]);
    }

    private static function shippingSection(): Section
    {
        return Section::make(__('users::resources.user.sections.shipping'))
            ->icon(Heroicon::Truck)
            ->schema([
                TextEntry::make('shipping_note')
                    ->label(__('users::resources.user.fields.shipping_text'))
                    ->html()
                    ->placeholder('-'),
            ]);
    }
}
