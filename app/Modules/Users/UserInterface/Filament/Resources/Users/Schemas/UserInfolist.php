<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    /**
     * Seams: Offers' statistics (D-05), the audit history, Extra Permissions
     * (ticket 08) and the active and password-change markers (tickets 06, 07)
     * add their sections here.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::accountSection(),
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
