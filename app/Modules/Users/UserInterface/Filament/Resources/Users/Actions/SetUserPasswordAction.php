<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\ChangeUserPassword\ChangeUserPasswordCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use App\Modules\Users\UserInterface\Support\KeepsOwnSessionAfterPasswordChange;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Template E, short form. FR-25, FR-36: the Admin sets a new password; for another User the handler
 * sets "password change required". The only place to set a password on an existing account.
 */
final class SetUserPasswordAction
{
    public static function make(): Action
    {
        return Action::make('set_password')
            ->label(__('users::resources.user.actions.set_password.label'))
            ->modalHeading(__('users::resources.user.actions.set_password.label'))
            ->modalDescription(__('users::resources.user.actions.set_password.description'))
            ->modalSubmitActionLabel(__('users::resources.user.actions.set_password.submit'))
            ->modalWidth('md')
            ->schema([
                TextInput::make('password')
                    ->label(__('users::resources.user.fields.password_new'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->maxLength(255)
                    ->suffixAction(
                        Action::make('generate-password')
                            ->label(__('users::resources.user.actions.generate_password'))
                            ->icon(Heroicon::ArrowPathRoundedSquare)
                            ->action(function (Set $set): void {
                                $generated = Str::password(12, true, true, true, false);
                                $set('password', $generated);
                                $set('password_confirmation', $generated);
                            })
                    ),

                TextInput::make('password_confirmation')
                    ->label(__('users::resources.user.fields.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password')
                    ->dehydrated(false),
            ])
            ->visible(fn (UserModel $record): bool => UserResource::canEdit($record))
            ->action(function (UserModel $record, array $data): void {
                CommandSender::send(new ChangeUserPasswordCommand(
                    actor: CurrentActor::get(),
                    userId: (int) $record->getKey(),
                    password: $data['password'],
                ));

                KeepsOwnSessionAfterPasswordChange::for((int) $record->getKey());

                $record->refresh();

                Notification::make()->success()->title(__('users::resources.user.actions.set_password.done'))->send();
            });
    }
}
