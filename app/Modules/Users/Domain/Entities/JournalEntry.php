<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Domain\Exceptions\JournalRefused;

/**
 * One line of a User's Journal (FR-29). Only its owner reads, reads-marks or
 * deletes it (RULE-12). Content is plain text; nothing here interprets markup.
 */
final class JournalEntry
{
    private const MAX_KIND_LENGTH = 50;

    private const MAX_TITLE_LENGTH = 255;

    private function __construct(
        private ?int $id,
        private readonly int $ownerId,
        private readonly string $kind,
        private readonly JournalSeverity $severity,
        private readonly string $title,
        private readonly string $content,
        private readonly ?int $subjectId,
        private bool $isRead,
    ) {}

    /** The kind names the area, as a lowercase word such as `offer` or `users`. */
    public static function write(
        int $ownerId,
        string $kind,
        JournalSeverity $severity,
        string $title,
        string $content,
        ?int $subjectId = null,
    ): self {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $kind) !== 1 || strlen($kind) > self::MAX_KIND_LENGTH) {
            throw JournalRefused::invalidKind();
        }

        $title = trim($title);

        if ($title === '' || mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw JournalRefused::invalidTitle();
        }

        return new self(null, $ownerId, $kind, $severity, $title, $content, $subjectId, false);
    }

    /** @internal for the mapper */
    public static function reconstitute(
        int $id,
        int $ownerId,
        string $kind,
        JournalSeverity $severity,
        string $title,
        string $content,
        ?int $subjectId,
        bool $isRead,
    ): self {
        return new self($id, $ownerId, $kind, $severity, $title, $content, $subjectId, $isRead);
    }

    public function markReadBy(int $userId): void
    {
        $this->ensureOwnedBy($userId);

        $this->isRead = true;
    }

    public function ensureOwnedBy(int $userId): void
    {
        if ($this->ownerId !== $userId) {
            throw JournalRefused::notOwner();
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function ownerId(): int
    {
        return $this->ownerId;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function severity(): JournalSeverity
    {
        return $this->severity;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function subjectId(): ?int
    {
        return $this->subjectId;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }
}
