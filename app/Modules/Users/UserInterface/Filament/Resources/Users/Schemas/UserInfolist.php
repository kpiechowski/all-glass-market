<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

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
            Section::make('User Details')
                ->aside()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('name')
                            ->columnSpan(1),

                        TextEntry::make('email')
                            ->columnSpan(1),

                        TextEntry::make('phone')
                            ->columnSpan(1),

                        IconEntry::make('email_verified_at')
                            ->label('Email verified')
                            ->boolean()
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make('Company Details')
                ->aside()
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        IconEntry::make('company_account')
                            ->label('Company account')
                            ->boolean()
                            ->columnSpan(1),

                        IconEntry::make('has_accepted_terms')
                            ->label('Accepted terms')
                            ->boolean()
                            ->columnSpan(1),

                        TextEntry::make('company_name')
                            ->columnSpan(1),

                        TextEntry::make('company_nip')
                            ->label('NIP')
                            ->columnSpan(1),

                        TextEntry::make('company_address')
                            ->columnSpan(1),

                        TextEntry::make('shipment_address')
                            ->columnSpan(1),

                        TextEntry::make('city')
                            ->columnSpan(1),

                        TextEntry::make('city_code')
                            ->label('Postal code')
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make('Shipping Information')
                ->aside()
                ->collapsed()
                ->schema([
                    TextEntry::make('shipping_information')
                        ->html()
                        ->columnSpanFull(),
                ]),

            Section::make('Timestamps')
                ->aside()
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->columnSpan(1),

                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->columnSpan(1),
                    ]),
                ]),
        ]);
    }
}
