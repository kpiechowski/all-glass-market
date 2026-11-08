<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\ReactivateUser\ReactivateUserCommand;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** WF-05: reactivation needs no confirmation; a grey secondary header action on an inactive record. */
final class ReactivateUserAction
{
    public static function make(): Action
    {
        return Action::make('reactivate')
            ->label(__('users::resources.user.actions.reactivate.label'))
            ->color('gray')
            ->visible(fn (UserModel $record): bool => ! $record->isActive()
                && CurrentActor::can(UsersPermission::ManageUsers)
                && RoleDisplay::canManage($record))
            ->action(function (UserModel $record): void {
                CommandSender::send(new ReactivateUserCommand(CurrentActor::get(), (int) $record->getKey()));

                $record->refresh();

                Notification::make()->success()->title(__('users::resources.user.actions.reactivate.done'))->send();
            });
    }
}
