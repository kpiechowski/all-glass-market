<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;

/** FR-26: the Admin's password actions, shared by the detail and edit pages. */
class PasswordActions
{
    public static function make(): ActionGroup
    {
        return ActionGroup::make([
            SendPasswordLinkAction::make(PasswordLinkKind::Invitation),
            SendPasswordLinkAction::make(PasswordLinkKind::Reset),
            SetPasswordChangeRequirementAction::make(true),
            SetPasswordChangeRequirementAction::make(false),
        ])
            ->label(__('users::resources.user.actions.group'))
            ->icon(Heroicon::Key)
            ->button();
    }
}
