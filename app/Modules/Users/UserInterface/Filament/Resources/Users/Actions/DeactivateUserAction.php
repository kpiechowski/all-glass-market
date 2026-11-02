<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\DeactivateUser\DeactivateUserCommand;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** WF-05: deactivation asks for confirmation; the handler enforces RULE-08 again. */
final class DeactivateUserAction
{
    public static function make(): Action
    {
        return Action::make('deactivate')
            ->label(__('users::resources.user.actions.deactivate.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('users::resources.user.actions.deactivate.heading'))
            ->modalDescription(__('users::resources.user.actions.deactivate.description'))
            ->visible(fn (UserModel $record): bool => $record->isActive()
                && CurrentActor::can(UsersPermission::ManageUsers)
                && RoleDisplay::canDeactivate($record))
            ->action(function (UserModel $record): void {
                CommandSender::send(new DeactivateUserCommand(CurrentActor::get(), (int) $record->getKey()));

                $record->refresh();

                Notification::make()->success()->title(__('users::resources.user.actions.deactivate.done'))->send();
            });
    }
}
