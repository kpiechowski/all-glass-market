<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Domain\Repositories\UserRepository;

/** What other modules may ask about a User's Account type ('private' or 'company'): Offers prices by it (FR-28). */
final class AccountTypes
{
    public function __construct(private UserRepository $users) {}

    /** The current Account type, or null when no such User exists. */
    public function of(int $userId): ?string
    {
        return $this->ofMany([$userId])[$userId] ?? null;
    }

    /**
     * One read for many Users (lists need one answer per row); unknown ids are absent.
     *
     * @param  list<int>  $userIds
     * @return array<int, string>
     */
    public function ofMany(array $userIds): array
    {
        return $this->users->accountTypesOf(array_values(array_unique($userIds)));
    }
}
