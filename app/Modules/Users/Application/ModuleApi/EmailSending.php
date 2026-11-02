<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

/**
 * Whether the application can send e-mail (spec §10). Production has none
 * today: "works" is detected as the default mailer being a real transport,
 * not `log` or `array`. While it does not, the invitation option is not
 * offered and links are not sent.
 */
final class EmailSending
{
    public function isAvailable(): bool
    {
        $mailer = (string) config('mail.default');

        return $mailer !== '' && ! in_array($mailer, ['log', 'array'], true);
    }
}
