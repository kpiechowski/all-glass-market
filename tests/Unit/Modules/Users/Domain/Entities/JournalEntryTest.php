<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Entities\JournalEntry;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Domain\Exceptions\JournalRefused;
use PHPUnit\Framework\TestCase;

class JournalEntryTest extends TestCase
{
    private function entry(int $ownerId = 7): JournalEntry
    {
        return JournalEntry::write($ownerId, 'offer', JournalSeverity::Success, 'Oferta aktywna', "Linia 1\nLinia 2", 12);
    }

    public function test_a_new_entry_is_unread_and_keeps_its_fields(): void
    {
        $entry = $this->entry();

        $this->assertNull($entry->id());
        $this->assertSame(7, $entry->ownerId());
        $this->assertSame('offer', $entry->kind());
        $this->assertSame(JournalSeverity::Success, $entry->severity());
        $this->assertSame('Oferta aktywna', $entry->title());
        $this->assertSame("Linia 1\nLinia 2", $entry->content());
        $this->assertSame(12, $entry->subjectId());
        $this->assertFalse($entry->isRead());
    }

    public function test_content_is_kept_exactly_as_typed(): void
    {
        $entry = JournalEntry::write(7, 'offer', JournalSeverity::Info, 'T', '<b>x</b> & <script>');

        $this->assertSame('<b>x</b> & <script>', $entry->content());
    }

    public function test_the_subject_is_optional(): void
    {
        $this->assertNull(JournalEntry::write(7, 'users', JournalSeverity::Info, 'T', 'C')->subjectId());
    }

    public function test_the_owner_marks_it_read(): void
    {
        $entry = $this->entry();

        $entry->markReadBy(7);

        $this->assertTrue($entry->isRead());
    }

    public function test_someone_else_cannot_mark_it_read(): void
    {
        $entry = $this->entry();

        try {
            $entry->markReadBy(8);
            $this->fail('A foreign mark was accepted');
        } catch (JournalRefused) {
            $this->assertFalse($entry->isRead());
        }
    }

    public function test_ownership_is_checked_for_deleting(): void
    {
        $this->entry()->ensureOwnedBy(7);

        $this->expectException(JournalRefused::class);
        $this->entry()->ensureOwnedBy(8);
    }

    public function test_a_kind_must_be_a_lowercase_word(): void
    {
        $this->expectException(JournalRefused::class);

        JournalEntry::write(7, 'Offer Kind', JournalSeverity::Info, 'T', 'C');
    }

    public function test_a_title_is_required(): void
    {
        $this->expectException(JournalRefused::class);

        JournalEntry::write(7, 'offer', JournalSeverity::Info, '  ', 'C');
    }

    public function test_a_title_has_a_length_limit(): void
    {
        $this->expectException(JournalRefused::class);

        JournalEntry::write(7, 'offer', JournalSeverity::Info, str_repeat('a', 256), 'C');
    }
}
