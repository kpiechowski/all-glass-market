<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\StartImpersonation\StartImpersonationCommand;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use App\Modules\Users\UserInterface\Support\ImpersonationSession;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

/** WF-06: absent when RULE-10 excludes the target; the handler checks again. */
final class ImpersonateUserAction
{
    public static function make(): Action
    {
        return Action::make('impersonate')
            ->label(__('users::resources.user.actions.impersonate.label'))
            ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
            ->requiresConfirmation()
            ->modalHeading(__('users::resources.user.actions.impersonate.heading'))
            ->modalDescription(__('users::resources.user.actions.impersonate.description'))
            ->modalSubmitActionLabel(__('users::resources.user.actions.impersonate.label'))
            ->visible(fn (UserModel $record): bool => CurrentActor::can(UsersPermission::Impersonate)
                && RoleDisplay::canImpersonate($record))
            ->action(function (UserModel $record, Component $livewire): void {
                CommandSender::send(new StartImpersonationCommand(
                    CurrentActor::get(),
                    (int) $record->getKey(),
                    ImpersonationSession::isActive(),
                ));

                ImpersonationSession::start(auth()->user(), $record);

                $livewire->redirect(Filament::getUrl());
            });
    }
}
