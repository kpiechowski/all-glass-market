<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Notifications;

use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The e-mail carrying a reset or invitation link (RULE-14). */
final class PasswordLinkNotification extends Notification
{
    public function __construct(
        public readonly PasswordLinkKind $kind,
        public readonly string $url,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = 'users::mail.'.$this->kind->value;

        return (new MailMessage)
            ->subject(__($key.'.subject'))
            ->greeting(__($key.'.greeting'))
            ->line(__($key.'.intro'))
            ->action(__($key.'.action'), $this->url)
            ->line(__($key.'.expires'))
            ->line(__($key.'.ignore'));
    }
}
