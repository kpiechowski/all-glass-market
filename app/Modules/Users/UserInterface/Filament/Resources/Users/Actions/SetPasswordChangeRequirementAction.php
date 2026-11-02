<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\SetPasswordChangeRequirement\SetPasswordChangeRequirementCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;

/** FR-26: set or clear "password change required" without changing the password. */
class SetPasswordChangeRequirementAction
{
    public static function make(bool $required): Action
    {
        $key = $required ? 'require_password_change' : 'clear_password_change';

        return Action::make($key)
            ->label(__('users::resources.user.actions.'.$key))
            ->requiresConfirmation()
            ->visible(fn (UserModel $record): bool => UserResource::canEdit($record) && (bool) $record->password_change_required !== $required)
            ->action(function (UserModel $record) use ($required): void {
                CommandSender::send(new SetPasswordChangeRequirementCommand(CurrentActor::get(), (int) $record->getKey(), $required));

                $record->refresh();
            });
    }
}
