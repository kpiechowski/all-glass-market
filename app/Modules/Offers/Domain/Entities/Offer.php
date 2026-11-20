<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;

/**
 * An Offer (§2): one physical piece (RULE-01). It has an owner who never changes (RULE-07) and a
 * number assigned once (RULE-13). It stores a Glass model's id or a hint, never glass data (RULE-02).
 * Only Draft exists in this slice. Whether a newly chosen Glass model or producer may be picked
 * (exists, not withdrawn) is a fact the handler gathers from Catalogue and passes in; a value the
 * Offer already has survives a save even when it was withdrawn since.
 */
final class Offer
{
    private function __construct(
        private ?int $id,
        private OfferNumber $number,
        private int $ownerId,
        private OfferStatus $status,
        private OfferContent $content,
    ) {}

    public static function draft(
        OfferNumber $number,
        int $ownerId,
        OfferContent $content,
        bool $glassModelCanBePicked,
        bool $producerCanBePicked,
    ): self {
        self::guardPicks(null, $content, $glassModelCanBePicked, $producerCanBePicked);

        return new self(null, $number, $ownerId, OfferStatus::Draft, $content);
    }

    /** @internal for the mapper */
    public static function reconstitute(int $id, OfferNumber $number, int $ownerId, OfferStatus $status, OfferContent $content): self
    {
        return new self($id, $number, $ownerId, $status, $content);
    }

    /** FR-06: saves any content and keeps the status. */
    public function revise(OfferContent $content, bool $glassModelCanBePicked, bool $producerCanBePicked): void
    {
        self::guardPicks($this->content, $content, $glassModelCanBePicked, $producerCanBePicked);

        $this->content = $content;
    }

    private static function guardPicks(?OfferContent $current, OfferContent $content, bool $glassModelCanBePicked, bool $producerCanBePicked): void
    {
        $glassModelId = $content->glass->glassModelId;
        if ($glassModelId !== null && $glassModelId !== $current?->glass->glassModelId && ! $glassModelCanBePicked) {
            throw OfferRefused::glassModelCannotBePicked();
        }

        if ($content->producerId !== null && $content->producerId !== $current?->producerId && ! $producerCanBePicked) {
            throw OfferRefused::producerCannotBePicked();
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function number(): OfferNumber
    {
        return $this->number;
    }

    public function ownerId(): int
    {
        return $this->ownerId;
    }

    public function status(): OfferStatus
    {
        return $this->status;
    }

    public function content(): OfferContent
    {
        return $this->content;
    }
}
