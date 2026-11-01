<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Domain\Contracts\JournalSubjectResolver;
use Tests\TestCase;

class JournalSubjectsTest extends TestCase
{
    private function resolver(): JournalSubjectResolver
    {
        return new class implements JournalSubjectResolver
        {
            public function urlFor(int $subjectId): ?string
            {
                return $subjectId === 1 ? '/oferta/1' : null;
            }
        };
    }

    public function test_it_resolves_a_registered_subject_by_kind(): void
    {
        $subjects = new JournalSubjects;
        $subjects->register('offer', $this->resolver());

        $this->assertSame('/oferta/1', $subjects->urlFor('offer', 1));
    }

    public function test_a_removed_subject_or_an_unregistered_kind_resolves_to_nothing(): void
    {
        $subjects = new JournalSubjects;
        $subjects->register('offer', $this->resolver());

        $this->assertNull($subjects->urlFor('offer', 2));
        $this->assertNull($subjects->urlFor('users', 1));
    }

    public function test_the_go_to_button_is_named_after_the_kind(): void
    {
        $subjects = new JournalSubjects;

        $this->assertSame('Przejdź do oferty', $subjects->goToSubjectLabel('offer'));
        $this->assertSame('Przejdź do obiektu', $subjects->goToSubjectLabel('warehouse'));
    }
}
