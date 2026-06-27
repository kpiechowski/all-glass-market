<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use App\Modules\Users\Domain\Enums\RoleEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('users::resource.sections.user_details'))
                ->aside()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('role')
                            ->label(__('users::resource.fields.role'))
                            ->formatStateUsing(fn (RoleEnum $state): string => __('users::resource.roles.'.$state->value))
                            ->badge()
                            ->color(fn (RoleEnum $state): string => match ($state) {
                                RoleEnum::Root => 'danger',
                                RoleEnum::Admin => 'warning',
                                RoleEnum::Client => 'gray',
                            })
                            ->columnSpan(1),

                        TextEntry::make('name')
                            ->label(__('users::resource.fields.name'))
                            ->columnSpan(1),

                        TextEntry::make('email')
                            ->label(__('users::resource.fields.email'))
                            ->columnSpan(1),

                        TextEntry::make('phone')
                            ->label(__('users::resource.fields.phone'))
                            ->columnSpan(1),

                        IconEntry::make('email_verified_at')
                            ->label(__('users::resource.fields.email_verified'))
                            ->boolean()
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make(__('users::resource.sections.company_details'))
                ->aside()
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
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
                            ->columnSpan(1),

                        TextEntry::make('company_nip')
                            ->label(__('users::resource.fields.company_nip'))
                            ->columnSpan(1),

                        TextEntry::make('company_address')
                            ->label(__('users::resource.fields.company_address'))
                            ->columnSpan(1),

                        TextEntry::make('shipment_address')
                            ->label(__('users::resource.fields.shipment_address'))
                            ->columnSpan(1),

                        TextEntry::make('city')
                            ->label(__('users::resource.fields.city'))
                            ->columnSpan(1),

                        TextEntry::make('city_code')
                            ->label(__('users::resource.fields.city_code'))
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make(__('users::resource.sections.shipping_information'))
                ->aside()
                ->collapsed()
                ->schema([
                    TextEntry::make('shipping_information')
                        ->label(__('users::resource.fields.shipping_information'))
                        ->html()
                        ->columnSpanFull(),
                ]),

            Section::make(__('users::resource.sections.timestamps'))
                ->aside()
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')
                            ->label(__('users::resource.fields.created_at'))
                            ->dateTime()
                            ->columnSpan(1),

                        TextEntry::make('updated_at')
                            ->label(__('users::resource.fields.updated_at'))
                            ->dateTime()
                            ->columnSpan(1),
                    ]),
                ]),
        ]);
    }
}
