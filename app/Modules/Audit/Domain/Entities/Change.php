<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Entities;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Domain\Exceptions\ChangeRefused;
use DateTimeImmutable;

/**
 * One Change entry (spec §8): what happened to one record, by whom, when.
 * Values are kept raw; names are resolved at display. The entry has no
 * behaviour after it is written: it is never edited (RULE-04).
 */
final class Change
{
    /**
     * @param  string  $recordType  the registration's stable key
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relationChanges
     */
    private function __construct(
        private ?int $id,
        private readonly string $recordType,
        private readonly int $recordId,
        private readonly ChangeKind $kind,
        private readonly ?string $title,
        private readonly array $changes,
        private readonly array $relationChanges,
        private readonly ?int $actorId,
        private readonly ?string $actorReason,
        private readonly DateTimeImmutable $occurredAt,
        private readonly ?int $restoredFromId = null,
        private readonly ?DateTimeImmutable $restoredFromAt = null,
    ) {}

    /**
     * Created holds the initial values; Deleted holds no values but the last
     * title; Restored holds none; Changed must hold at least one change.
     *
     * @param  string  $recordType  the registration's stable key
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relationChanges
     */
    public static function record(
        string $recordType,
        int $recordId,
        ChangeKind $kind,
        ?string $title,
        array $changes,
        array $relationChanges,
        ?int $actorId,
        ?string $actorReason,
        DateTimeImmutable $occurredAt,
        ?int $restoredFromId = null,
        ?DateTimeImmutable $restoredFromAt = null,
    ): self {
        if (($actorId === null) === ($actorReason === null || trim($actorReason) === '')) {
            throw ChangeRefused::invalidActor();
        }

        if ($kind === ChangeKind::Changed && $changes === [] && $relationChanges === []) {
            throw ChangeRefused::nothingChanged();
        }

        if ($kind === ChangeKind::Deleted || $kind === ChangeKind::Restored) {
            $changes = [];
            $relationChanges = [];
        }

        if ($kind !== ChangeKind::Deleted) {
            $title = null;
        }

        if ($restoredFromId !== null && ($kind !== ChangeKind::Changed || $restoredFromAt === null)) {
            throw ChangeRefused::invalidRestoreMarker();
        }

        return new self(null, $recordType, $recordId, $kind, $title, $changes, $relationChanges, $actorId, $actorReason, $occurredAt, $restoredFromId, $restoredFromAt);
    }

    /**
     * @internal for the mapper
     *
     * @param  string  $recordType  the registration's stable key
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relationChanges
     */
    public static function reconstitute(
        int $id,
        string $recordType,
        int $recordId,
        ChangeKind $kind,
        ?string $title,
        array $changes,
        array $relationChanges,
        ?int $actorId,
        ?string $actorReason,
        DateTimeImmutable $occurredAt,
        ?int $restoredFromId = null,
        ?DateTimeImmutable $restoredFromAt = null,
    ): self {
        return new self($id, $recordType, $recordId, $kind, $title, $changes, $relationChanges, $actorId, $actorReason, $occurredAt, $restoredFromId, $restoredFromAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function recordType(): string
    {
        return $this->recordType;
    }

    public function recordId(): int
    {
        return $this->recordId;
    }

    public function kind(): ChangeKind
    {
        return $this->kind;
    }

    /** The record's last title; kept for Deleted only (D-18). */
    public function title(): ?string
    {
        return $this->title;
    }

    /** @return array<string, array{old: mixed, new: mixed}> */
    public function changes(): array
    {
        return $this->changes;
    }

    /** @return array<string, array{added: list<int|string>, removed: list<int|string>}> */
    public function relationChanges(): array
    {
        return $this->relationChanges;
    }

    public function actorId(): ?int
    {
        return $this->actorId;
    }

    public function actorReason(): ?string
    {
        return $this->actorReason;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /** The entry a Restore wrote back from; set only on a Changed entry made by a Restore (FR-14, D-38). */
    public function restoredFromId(): ?int
    {
        return $this->restoredFromId;
    }

    /** When the source entry was made, kept so the marker survives the source's retention. */
    public function restoredFromAt(): ?DateTimeImmutable
    {
        return $this->restoredFromAt;
    }
}
