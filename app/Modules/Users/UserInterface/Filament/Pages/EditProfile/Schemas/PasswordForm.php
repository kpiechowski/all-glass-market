<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/** The fields of the "Zmień hasło" modal (template E) on "Mój profil" (FR-14). */
class PasswordForm
{
    /** @return list<Component|TextInput> */
    public static function components(): array
    {
        return [
            TextInput::make('password')
                ->label(__('users::profile.fields.password'))
                ->helperText(__('users::profile.helpers.password'))
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->maxLength(255)
                ->suffixAction(
                    Action::make('generate-password')
                        ->label(__('users::profile.actions.generate_password'))
                        ->icon(Heroicon::ArrowPathRoundedSquare)
                        ->action(function (Set $set): void {
                            $generated = Str::password(20, true, true, true, false);
                            $set('password', $generated);
                            $set('password_confirmation', $generated);
                        })
                ),

            TextInput::make('password_confirmation')
                ->label(__('users::profile.fields.password_confirmation'))
                ->password()
                ->revealable()
                ->required()
                ->same('password')
                ->dehydrated(false),
        ];
    }
}
