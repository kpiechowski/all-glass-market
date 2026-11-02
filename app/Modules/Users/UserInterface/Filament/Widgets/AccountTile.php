<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Widgets;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/** FR-34: the dashboard's first tile. Read-only; the sign-out is Filament's own logout route. */
class AccountTile extends Widget
{
    protected static ?int $sort = -10;

    protected string $view = 'users::widgets.account-tile';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var UserModel $user */
        $user = Filament::auth()->user();

        return [
            'user' => $user,
            'accountTypeLabel' => __('users::widgets.account_tile.'.($user->account_type === AccountType::Company ? 'company' : 'private')),
            'profileUrl' => EditProfile::getUrl(),
        ];
    }
}
