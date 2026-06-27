<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Domain\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
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

                    Tab::make(__('users::resource.tabs.timestamps'))
                        ->icon(Heroicon::Clock)
                        ->schema(self::timestampsTab()),
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
                    TextEntry::make('role')
                        ->label(__('users::resource.fields.role'))
                        ->badge()
                        ->columnSpan(1),

                    TextEntry::make('name')
                        ->label(__('users::resource.fields.name'))
                        ->columnSpan(1),

                    TextEntry::make('email')
                        ->label(__('users::resource.fields.email'))
                        ->icon(Heroicon::Envelope)
                        ->copyable()
                        ->columnSpan(1),

                    TextEntry::make('phone')
                        ->label(__('users::resource.fields.phone'))
                        ->icon(Heroicon::Phone)
                        ->placeholder('-')
                        ->columnSpan(1),

                    IconEntry::make('email_verified_at')
                        ->label(__('users::resource.fields.email_verified'))
                        ->boolean()
                        ->trueColor('success')
                        ->falseColor('danger')
                        ->columnSpan(1),
                ]),

            Section::make(__('users::resource.sections.company_details'))
                ->contained(false)
                ->icon(Heroicon::BuildingOffice)
                ->columns(2)
                ->visible(fn (User $record): bool => $record->isCompanyAccount())
                ->schema([
                    IconEntry::make('company_account')
                        ->label(__('users::resource.fields.company_account'))
                        ->boolean()
                        ->columnSpan(1),

                    IconEntry::make('has_accepted_terms')
                        ->label(__('users::resource.fields.accepted_terms'))
                        ->boolean()
                        ->columnSpan(1),

                    TextEntry::make('company_name')
                        ->label(__('users::resource.fields.company_name'))
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('company_nip')
                        ->label(__('users::resource.fields.company_nip'))
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('company_address')
                        ->label(__('users::resource.fields.company_address'))
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('shipment_address')
                        ->label(__('users::resource.fields.shipment_address'))
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('city')
                        ->label(__('users::resource.fields.city'))
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('city_code')
                        ->label(__('users::resource.fields.city_code'))
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('shipping_note')
                        ->label(__('users::resource.fields.shipping_note'))
                        ->html()
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),
        ];
    }

    /** @return array<mixed> */
    private static function timestampsTab(): array
    {
        return [
            Section::make(__('users::resource.sections.timestamps'))
                ->contained(false)
                ->icon(Heroicon::Clock)
                ->columns(1)
                ->schema([
                    TextEntry::make('created_at')
                        ->label(__('users::resource.fields.created_at'))
                        ->icon(Heroicon::PlusCircle)
                        ->inlineLabel()
                        ->dateTime(),

                    TextEntry::make('updated_at')
                        ->label(__('users::resource.fields.updated_at'))
                        ->icon(Heroicon::PencilSquare)
                        ->inlineLabel()
                        ->dateTime(),
                ]),
        ];
    }
}
