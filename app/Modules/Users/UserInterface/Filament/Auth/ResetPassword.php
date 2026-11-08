<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Auth;

use App\Core\Domain\Actor;
use App\Core\UserInterface\CommandSender;
use App\Modules\Users\Application\Commands\SetPasswordFromLink\SetPasswordFromLinkCommand;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Facades\Filament;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Sets a password from a reset link (FR-11, WF-03) and signs the User in.
 * Filament's page saves the model and skips its panel check; here a command
 * redeems the single-use token and decides who may reset (RULE-14), so an
 * expired or used link is refused with a message. Other sessions end because
 * the stored password hash changed (FR-10).
 */
class ResetPassword extends BaseResetPassword
{
    protected function linkKind(): PasswordLinkKind
    {
        return PasswordLinkKind::Reset;
    }

    #[Exposed]
    public function resetPassword(): ?PasswordResetResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        if ($this->isResetPasswordRateLimited($this->email)) {
            return null;
        }

        $data = $this->form->getState();

        try {
            $userId = CommandSender::send(new SetPasswordFromLinkCommand(
                Actor::system($this->linkKind() === PasswordLinkKind::Invitation ? 'users::actors.invitation_accepted' : 'users::actors.password_reset_redeemed'),
                (string) $this->email,
                (string) $this->token,
                $this->linkKind()->value,
                (string) $data['password'],
            ));
        } catch (Halt) {
            return null;
        }

        Filament::auth()->login(UserModel::query()->findOrFail($userId));
        session()->regenerate();

        $this->redirect(Filament::getUrl());

        return null;
    }

    #[Unexposed]
    public function getTitle(): string|Htmlable
    {
        return __('users::login.reset.title');
    }

    #[Unexposed]
    public function getHeading(): string|Htmlable|null
    {
        return __('users::login.reset.heading');
    }
}
