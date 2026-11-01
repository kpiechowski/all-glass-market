<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class PasswordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('passwordData')
            ->components([
                Section::make(__('users::profile.sections.change_password'))
                    ->aside()
                    ->description(__('users::profile.sections.change_password_description'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('users::profile.fields.password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255)
                            ->suffixAction(
                                Action::make('generate-password')
                                    ->icon(Heroicon::ArrowPathRoundedSquare)
                                    ->action(function (Set $set): void {
                                        $generated = Str::password(20, true, true, true, false);
                                        $set('password', $generated);
                                        $set('password_confirmation', $generated);
                                    })
                            )
                            ->columnSpanFull(),

                        TextInput::make('password_confirmation')
                            ->label(__('users::profile.fields.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password')
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
