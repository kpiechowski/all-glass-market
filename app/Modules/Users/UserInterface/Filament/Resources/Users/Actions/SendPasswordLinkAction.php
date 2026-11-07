<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\SendPasswordLink\SendPasswordLinkCommand;
use App\Modules\Users\Application\ModuleApi\EmailSending;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** FR-26: re-send an invitation or a password-reset link, only while e-mail sending works. */
class SendPasswordLinkAction
{
    public static function make(PasswordLinkKind $kind): Action
    {
        return Action::make('send_'.$kind->value.'_link')
            ->label(__('users::resources.user.actions.send_'.$kind->value))
            ->requiresConfirmation()
            ->modalSubmitActionLabel(__('users::resources.user.actions.send'))
            ->visible(fn (UserModel $record): bool => UserResource::canEdit($record) && app(EmailSending::class)->isAvailable())
            ->action(function (UserModel $record) use ($kind): void {
                CommandSender::send(new SendPasswordLinkCommand(CurrentActor::get(), (int) $record->getKey(), $kind->value));

                Notification::make()
                    ->success()
                    ->title(__('users::resources.user.actions.'.$kind->value.'_sent'))
                    ->send();
            });
    }
}
