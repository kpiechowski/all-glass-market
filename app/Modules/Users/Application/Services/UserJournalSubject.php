<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Services;

use App\Modules\Users\Application\ModuleApi\UserUrls;
use App\Modules\Users\Domain\Contracts\JournalSubjectResolver;
use App\Modules\Users\Domain\Repositories\UserRepository;

/** Journal entries of kind `users` point at a User's detail page (FR-21). */
final class UserJournalSubject implements JournalSubjectResolver
{
    public function __construct(
        private UserRepository $users,
        private UserUrls $urls,
    ) {}

    public function urlFor(int $subjectId): ?string
    {
        return $this->users->exists($subjectId) ? $this->urls->show($subjectId) : null;
    }
}
