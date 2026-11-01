<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Domain\Contracts\JournalSubjectResolver;
use Illuminate\Support\Facades\Lang;

/**
 * Where the module owning a kind of Journal entry says how to reach its
 * subjects, for example Offers registering `offer` in its provider's boot():
 *
 *     $subjects->register('offer', new OfferJournalSubject);
 *
 * Users stays ignorant of the subjects themselves (FR-31, FR-32). A kind
 * nobody registered behaves as a subject that no longer exists.
 */
final class JournalSubjects
{
    /** @var array<string, JournalSubjectResolver> */
    private array $resolvers = [];

    public function register(string $kind, JournalSubjectResolver $resolver): void
    {
        $this->resolvers[$kind] = $resolver;
    }

    /** The subject's address, or null when it is gone or its kind has no resolver. */
    public function urlFor(string $kind, int $subjectId): ?string
    {
        return ($this->resolvers[$kind] ?? null)?->urlFor($subjectId);
    }

    /** The button named after the kind: `offer` reads "Przejdź do oferty". */
    public function goToSubjectLabel(string $kind): string
    {
        $key = 'users::notifications.journal_entry.go_to_subject.'.$kind;

        return Lang::has($key) ? __($key) : __('users::notifications.journal_entry.go_to_subject.default');
    }
}
