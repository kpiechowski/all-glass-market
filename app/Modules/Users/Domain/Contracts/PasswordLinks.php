<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Contracts;

use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Exceptions\UserRefused;

/**
 * Single-use, time-limited links that let a User set a password (RULE-14):
 * a reset link (60 minutes) or an invitation link (7 days).
 */
interface PasswordLinks
{
    /**
     * Replaces any earlier link of that kind and e-mails the new one.
     *
     * @throws UserRefused when the e-mail cannot be sent
     */
    public function send(int $userId, PasswordLinkKind $kind): void;

    /**
     * Uses up a link: the id of the User it belongs to, or null when the link
     * is expired, used or unknown.
     */
    public function redeem(string $email, string $token, PasswordLinkKind $kind): ?int;
}
