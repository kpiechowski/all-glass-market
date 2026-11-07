<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

/**
 * Template C, layout (b): status, role, account type, the seller declaration and the dates stay
 * in view in the side column; company data, pickup address and shipping text fill the main one.
 * Users have no avatar, so the identity header shows name, e-mail and phone only.
 * Seam: Offers' statistics (D-05) add their section here.
 */
class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::identityHeader(),
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Group::make([self::companySection(), self::pickupAddressSection(), self::shippingSection()])
                    ->columnSpan(['default' => 'full', 'lg' => 2])
                    ->columnOrder(['default' => 2, 'lg' => 1]),
                Group::make([self::accountSection()])
                    ->columnSpan(['default' => 'full', 'lg' => 1])
                    ->columnOrder(['default' => 1, 'lg' => 2]),
            ]),
        ]);
    }

    /** Name and the contacts; hidden labels stay for screen readers. */
    private static function identityHeader(): Section
    {
        return Section::make()
            ->extraAttributes(['class' => 'infolist--identity'])
            ->schema([
                TextEntry::make('name')
                    ->label(__('users::resources.user.fields.name'))
                    ->hiddenLabel()
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold),
                TextEntry::make('email')
                    ->label(__('users::resources.user.fields.email'))
                    ->hiddenLabel()
                    ->copyable(),
                TextEntry::make('phone')
                    ->label(__('users::resources.user.fields.phone'))
                    ->hiddenLabel()
                    ->copyable()
                    ->visible(fn (UserModel $record): bool => filled($record->phone)),
            ]);
    }

    /** Side column: state and metadata, one sheet column. */
    private static function accountSection(): Section
    {
        return Section::make(__('users::resources.user.sections.account'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('status')
                    ->label(__('users::resources.user.fields.status'))
                    ->badge()
                    ->state(fn (UserModel $record): array => self::statusBadges($record))
                    ->color(fn (string $state): string => self::statusColor($state)),

                TextEntry::make('role')
                    ->label(__('users::resources.user.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => RoleDisplay::label($state))
                    ->color(fn (?string $state): string => RoleDisplay::color($state)),

                // FR-06: listed apart from the Role, for Super Admins only.
                TextEntry::make('extra_permissions')
                    ->label(__('users::resources.user.fields.extra_permissions'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => RoleDisplay::permissionLabel($state))
                    ->placeholder('—')
                    ->visible(fn (): bool => RoleDisplay::actorSeesExtraPermissions()),

                TextEntry::make('account_type')
                    ->label(__('users::resources.user.fields.account_type'))
                    ->badge(),

                TextEntry::make('seller_declaration_accepted_at')
                    ->label(__('users::resources.user.fields.declaration_accepted_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->visible(fn (UserModel $record): bool => $record->account_type === AccountType::Company),

                TextEntry::make('seller_declaration_version')
                    ->label(__('users::resources.user.fields.declaration_version'))
                    ->placeholder('—')
                    ->visible(fn (UserModel $record): bool => $record->account_type === AccountType::Company),

                TextEntry::make('created_at')
                    ->label(__('users::resources.user.fields.created_at'))
                    ->date('d.m.Y'),

                TextEntry::make('deactivated_at')
                    ->label(__('users::resources.user.fields.deactivated_at'))
                    ->date('d.m.Y')
                    ->visible(fn (UserModel $record): bool => ! $record->isActive()),
            ]);
    }

    /** Short values paired by meaning (name + NIP); the address spans the row. */
    private static function companySection(): Section
    {
        return Section::make(__('users::resources.user.sections.company_details'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->columns(['default' => 1, 'xl' => 2])
            ->schema([
                TextEntry::make('company_name')->label(__('users::resources.user.fields.company_name'))->placeholder('—'),
                TextEntry::make('company_nip')->label(__('users::resources.user.fields.company_nip'))->copyable()->placeholder('—'),
                TextEntry::make('company_address')->label(__('users::resources.user.fields.company_address'))->placeholder('—')->columnSpanFull(),
            ]);
    }

    private static function pickupAddressSection(): Section
    {
        return Section::make(__('users::resources.user.sections.pickup_address'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->columns(['default' => 1, 'xl' => 2])
            ->schema([
                TextEntry::make('shipment_address')->label(__('users::resources.user.fields.street'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('city_code')->label(__('users::resources.user.fields.postal_code'))->placeholder('—'),
                TextEntry::make('city')->label(__('users::resources.user.fields.city'))->placeholder('—'),
            ]);
    }

    /** The section's only entry is named like the section, so its label is hidden. */
    private static function shippingSection(): Section
    {
        return Section::make(__('users::resources.user.sections.shipping'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('shipping_note')
                    ->label(__('users::resources.user.sections.shipping'))
                    ->hiddenLabel()
                    ->html()
                    ->placeholder('—'),
            ]);
    }

    /**
     * The state badge, plus "Wymaga zmiany hasła" as an attention indicator beside it.
     *
     * @return list<string>
     */
    private static function statusBadges(UserModel $record): array
    {
        $badges = [$record->isActive()
            ? __('users::resources.user.status.active')
            : __('users::resources.user.status.inactive')];

        if ($record->password_change_required) {
            $badges[] = __('users::resources.user.markers.password_change_required');
        }

        return $badges;
    }

    private static function statusColor(string $state): string
    {
        return match ($state) {
            __('users::resources.user.status.active') => 'success',
            __('users::resources.user.status.inactive') => 'danger',
            default => 'warning',
        };
    }
}
