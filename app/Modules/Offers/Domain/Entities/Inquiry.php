<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use Carbon\CarbonImmutable;

/**
 * FR-21, FR-22, §7: an admin's question to an Offer's owner about one piece, with the sending admin recorded
 * (D-35), an optional channel and a note that becomes the Reservation's note when the owner accepts. It holds the
 * Offer's id; it is its own entity because any number are open on one Offer and each ends on its own. Sent becomes
 * Read when the owner opens it, then Accepted or Rejected; Sent or Read becomes Outdated. The end states are final.
 */
final class Inquiry
{
    private function __construct(
        private ?int $id,
        private int $offerId,
        private int $senderId,
        private ?SaleChannel $channel,
        private string $note,
        private InquiryState $state,
        private CarbonImmutable $sentAt,
        private ?CarbonImmutable $readAt,
        private ?CarbonImmutable $answeredAt,
    ) {}

    /** The handler has checked who sends and says whether the Offer can receive an Inquiry (FR-21). */
    public static function send(int $offerId, int $senderId, ?SaleChannel $channel, string $note, CarbonImmutable $now, bool $offerCanReceiveInquiry): self
    {
        if (! $offerCanReceiveInquiry) {
            throw OfferRefused::offerCannotReceiveInquiry();
        }

        $note = trim($note);

        if ($note === '') {
            throw OfferRefused::inquiryNoteRequired();
        }

        if (mb_strlen($note) > OfferDomainPolicy::MAX_INQUIRY_NOTE_LENGTH) {
            throw OfferRefused::inquiryNoteIsTooLong(OfferDomainPolicy::MAX_INQUIRY_NOTE_LENGTH);
        }

        return new self(null, $offerId, $senderId, $channel, $note, InquiryState::Sent, $now, null, null);
    }

    /** @internal for the mapper */
    public static function reconstitute(
        int $id,
        int $offerId,
        int $senderId,
        ?SaleChannel $channel,
        string $note,
        InquiryState $state,
        CarbonImmutable $sentAt,
        ?CarbonImmutable $readAt,
        ?CarbonImmutable $answeredAt,
    ): self {
        return new self($id, $offerId, $senderId, $channel, $note, $state, $sentAt, $readAt, $answeredAt);
    }

    /** FR-22: opening it marks it read; opening it again, or after it is closed, changes nothing. */
    public function markRead(CarbonImmutable $now): void
    {
        if (! OfferDomainPolicy::inquiryAwaitsReading($this->state)) {
            return;
        }

        $this->state = InquiryState::Read;
        $this->readAt = $now;
    }

    /** FR-22: the owner accepts it; the handler then reserves the Offer. */
    public function accept(CarbonImmutable $now): void
    {
        $this->answer(InquiryState::Accepted, $now);
    }

    /** FR-22. */
    public function reject(CarbonImmutable $now): void
    {
        $this->answer(InquiryState::Rejected, $now);
    }

    /** FR-23: the Offer stopped being Active. */
    public function outdate(): void
    {
        $this->guardOpen();

        $this->state = InquiryState::Outdated;
    }

    private function answer(InquiryState $state, CarbonImmutable $now): void
    {
        $this->guardOpen();

        $this->readAt ??= $now;
        $this->state = $state;
        $this->answeredAt = $now;
    }

    private function guardOpen(): void
    {
        if (! $this->state->isOpen()) {
            throw OfferRefused::inquiryIsClosed();
        }
    }

    public function isOpen(): bool
    {
        return $this->state->isOpen();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function offerId(): int
    {
        return $this->offerId;
    }

    public function senderId(): int
    {
        return $this->senderId;
    }

    public function channel(): ?SaleChannel
    {
        return $this->channel;
    }

    public function note(): string
    {
        return $this->note;
    }

    public function state(): InquiryState
    {
        return $this->state;
    }

    public function sentAt(): CarbonImmutable
    {
        return $this->sentAt;
    }

    public function readAt(): ?CarbonImmutable
    {
        return $this->readAt;
    }

    public function answeredAt(): ?CarbonImmutable
    {
        return $this->answeredAt;
    }
}
