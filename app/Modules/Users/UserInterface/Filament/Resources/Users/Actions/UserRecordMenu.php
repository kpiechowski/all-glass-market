<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Actions;

use Filament\Actions\ActionGroup;

/**
 * The "⋮" of the user's view and edit pages: the password block, then impersonation and the
 * dangerous Deactivate as a red menu item (admin-ui-design, Action hierarchy).
 */
final class UserRecordMenu
{
    public static function make(): ActionGroup
    {
        return ActionGroup::make([
            PasswordActions::make(),
            ActionGroup::make([
                ImpersonateUserAction::make(),
                DeactivateUserAction::make(),
            ])->dropdown(false),
        ]);
    }
}
