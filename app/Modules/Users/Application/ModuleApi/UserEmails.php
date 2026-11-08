<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Domain\Repositories\UserRepository;

/** What other modules may ask about who a User is: the e-mail behind an id (Audit's security entries). */
final class UserEmails
{
    public function __construct(private UserRepository $users) {}

    /** The User's e-mail, or null when no such User exists. */
    public function emailOf(int $userId): ?string
    {
        return $this->users->exists($userId) ? $this->users->get($userId)->email()->value() : null;
    }
}
