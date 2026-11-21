<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use Carbon\CarbonImmutable;

/**
 * FR-16, FR-17, RULE-05: a Reservation of one Offer, with a free-text "for whom" note, its author and time.
 * It is an entity of its own (module-persistence): it is ended on its own and stays as history, so it holds the
 * Offer's id and does not live inside the Offer. Whether the author was an admin is kept as it was when made,
 * because it decides who may release or sell it. A Reservation never expires; it ends released or sold.
 */
final class Reservation
{
    private function __construct(
        private ?int $id,
        private int $offerId,
        private int $authorId,
        private bool $madeByAdmin,
        private string $note,
        private CarbonImmutable $madeAt,
        private ?ReservationEnd $endedAs,
        private ?CarbonImmutable $endedAt,
    ) {}

    public static function make(int $offerId, int $authorId, bool $madeByAdmin, string $note, CarbonImmutable $madeAt): self
    {
        $note = trim($note);

        if ($note === '') {
            throw OfferRefused::reservationNoteRequired();
        }

        if (mb_strlen($note) > OfferDomainPolicy::MAX_RESERVATION_NOTE_LENGTH) {
            throw OfferRefused::reservationNoteIsTooLong(OfferDomainPolicy::MAX_RESERVATION_NOTE_LENGTH);
        }

        return new self(null, $offerId, $authorId, $madeByAdmin, $note, $madeAt, null, null);
    }

    /** @internal for the mapper */
    public static function reconstitute(
        int $id,
        int $offerId,
        int $authorId,
        bool $madeByAdmin,
        string $note,
        CarbonImmutable $madeAt,
        ?ReservationEnd $endedAs,
        ?CarbonImmutable $endedAt,
    ): self {
        return new self($id, $offerId, $authorId, $madeByAdmin, $note, $madeAt, $endedAs, $endedAt);
    }

    /** RULE-05: an admin's Reservation is released or sold by an admin only. */
    public function guardCanBeHandledBy(bool $actorIsAdmin): void
    {
        if (! OfferDomainPolicy::reservationCanBeHandledBy($this->madeByAdmin, $actorIsAdmin)) {
            throw OfferRefused::reservationIsLocked();
        }
    }

    public function end(ReservationEnd $how, CarbonImmutable $now): void
    {
        if (! $this->isCurrent()) {
            throw OfferRefused::reservationAlreadyEnded();
        }

        $this->endedAs = $how;
        $this->endedAt = $now;
    }

    public function isCurrent(): bool
    {
        return $this->endedAs === null;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function offerId(): int
    {
        return $this->offerId;
    }

    public function authorId(): int
    {
        return $this->authorId;
    }

    public function madeByAdmin(): bool
    {
        return $this->madeByAdmin;
    }

    public function note(): string
    {
        return $this->note;
    }

    public function madeAt(): CarbonImmutable
    {
        return $this->madeAt;
    }

    public function endedAs(): ?ReservationEnd
    {
        return $this->endedAs;
    }

    public function endedAt(): ?CarbonImmutable
    {
        return $this->endedAt;
    }
}
