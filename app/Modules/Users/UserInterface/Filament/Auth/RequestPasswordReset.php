<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Auth;

use App\Core\Domain\Actor;
use App\Core\UserInterface\CommandSender;
use App\Modules\Users\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Filament\Support\Components\Attributes\Exposed;

/**
 * "Nie pamiętasz hasła?" (FR-11, WF-03). The handler decides whether a link
 * goes out; this page answers every request the same way (RULE-14), where
 * Filament's own page would name the failure.
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    #[Exposed]
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        CommandSender::send(new RequestPasswordResetCommand(
            Actor::system('guest password reset request'),
            (string) $data['email'],
        ));

        Notification::make()
            ->title(__('users::login.reset_requested.title'))
            ->body(__('users::login.reset_requested.body'))
            ->success()
            ->send();

        $this->form->fill();
    }
}
