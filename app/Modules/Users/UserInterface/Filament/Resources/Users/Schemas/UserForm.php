<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Schemas;

use Filament\Actions\Action;
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
            Section::make('Details')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(1),
                ]),

            Section::make('Security')
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn(string $operation): bool => $operation === 'create')
                        ->dehydrated(fn(?string $state): bool => filled($state))
                        ->minLength(8)
                        // generate strong password without weird characters that may cause issues in some password fields
                        ->suffixAction(
                            Action::make('generate-password')
                                ->icon(Heroicon::ArrowPathRoundedSquare)
                                ->action(fn(Set $set) => $set('password', Str::password(12, true, true, true, false)))
                        )
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->requiredWith('password')
                        ->hiddenOn('create')
                        ->same('password')
                        ->dehydrated(false)
                        ->columnSpan(1),
                ]),
        ]);
    }
}
