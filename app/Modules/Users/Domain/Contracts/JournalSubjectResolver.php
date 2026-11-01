<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Contracts;

/**
 * Implemented by the module that owns the subject of Journal entries of one
 * kind (Offers for `offer`) and registered with `JournalSubjects`.
 */
interface JournalSubjectResolver
{
    /** The address of the subject, or null when it no longer exists (FR-31). */
    public function urlFor(int $subjectId): ?string;
}
