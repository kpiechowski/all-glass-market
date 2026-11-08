<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use Filament\Actions\ActionGroup;

/** FR-25, FR-26: the Admin's password actions, one block inside the record's "⋮". */
class PasswordActions
{
    public static function make(): ActionGroup
    {
        return ActionGroup::make([
            SetUserPasswordAction::make(),
            SendPasswordLinkAction::make(PasswordLinkKind::Invitation),
            SendPasswordLinkAction::make(PasswordLinkKind::Reset),
            SetPasswordChangeRequirementAction::make(true),
            SetPasswordChangeRequirementAction::make(false),
        ])->dropdown(false);
    }
}
