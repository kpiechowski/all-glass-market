<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->columnSpan(1),

                    TextEntry::make('email')
                        ->columnSpan(1),

                    IconEntry::make('email_verified_at')
                        ->label('Email verified')
                        ->boolean()
                        ->trueColor('success')
                        ->falseColor('danger')
                        ->columnSpan(1),
                ]),

            Section::make('Timestamps')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')
                        ->dateTime()
                        ->columnSpan(1),

                    TextEntry::make('updated_at')
                        ->dateTime()
                        ->columnSpan(1),
                ]),
        ]);
    }
}
