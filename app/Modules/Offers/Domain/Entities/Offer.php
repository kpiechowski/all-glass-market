<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use Carbon\CarbonImmutable;

/**
 * An Offer (§2): one physical piece (RULE-01). It has an owner who never changes (RULE-07) and a
 * number assigned once (RULE-13). It stores a Glass model's id or a hint, never glass data (RULE-02).
 * Statuses are Draft, Pending, Rejected, Active, Reserved, Sold and Withdrawn (§7); the status machine is this class
 * (RULE-04): sending, approving, rejecting, withdrawing, resuming, reserving, releasing, selling, returning a
 * sale and editing are the only ways to move between them. The Reservation and the Sale are entities of their
 * own that hold the Offer's id; the handler changes both in one command, which is one transaction (RULE-10). The Trash is not a status (D-30): an Offer in it keeps its status and is frozen until restored.
 * Whether a newly chosen Glass model or producer may be picked
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
        private OfferPhotos $photos,
        private ?CarbonImmutable $firstApprovedAt,
        private ?CarbonImmutable $lastApprovedAt,
        private bool $changedSinceApproval,
        private ?CarbonImmutable $trashedAt,
    ) {}

    public static function draft(
        OfferNumber $number,
        int $ownerId,
        OfferContent $content,
        bool $glassModelCanBePicked,
        bool $producerCanBePicked,
    ): self {
        self::guardPicks(null, $content, $glassModelCanBePicked, $producerCanBePicked);

        return new self(null, $number, $ownerId, OfferStatus::Draft, $content, OfferPhotos::none(), null, null, false, null);
    }

    /** @internal for the mapper */
    public static function reconstitute(
        int $id,
        OfferNumber $number,
        int $ownerId,
        OfferStatus $status,
        OfferContent $content,
        OfferPhotos $photos,
        ?CarbonImmutable $firstApprovedAt = null,
        ?CarbonImmutable $lastApprovedAt = null,
        bool $changedSinceApproval = false,
        ?CarbonImmutable $trashedAt = null,
    ): self {
        return new self($id, $number, $ownerId, $status, $content, $photos, $firstApprovedAt, $lastApprovedAt, $changedSinceApproval, $trashedAt);
    }

    /**
     * §7 Draft or Rejected to Pending (a seller) or Active (an admin's own Offer with a Glass model,
     * FR-13). FR-06 must hold first; the refusal names what is missing.
     */
    public function send(bool $ownerIsAdmin, CarbonImmutable $now): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeSent($this->status)) {
            throw OfferRefused::cannotBeSent();
        }

        $this->guardCompleteForSending($this->content, $this->photos);

        if ($ownerIsAdmin && $this->content->glass->glassModelId !== null) {
            $this->activate($now);

            return;
        }

        $this->status = OfferStatus::Pending;
    }

    /**
     * FR-09, RULE-03: Pending or Rejected to Active, only with a Glass model. A Glass model the
     * admin chose while approving (FR-10) is selected for the Offer first and replaces the hint.
     */
    public function approve(CarbonImmutable $now, ?int $chosenGlassModelId = null, bool $chosenGlassModelCanBePicked = true): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::approvalApplies($this->status)) {
            throw OfferRefused::cannotBeApproved();
        }

        $glass = $this->content->glass;

        if ($chosenGlassModelId !== null && $chosenGlassModelId !== $glass->glassModelId) {
            if (! $chosenGlassModelCanBePicked) {
                throw OfferRefused::glassModelCannotBePicked();
            }

            $glass = GlassReference::from($chosenGlassModelId, null);
        }

        if (! OfferDomainPolicy::canBeApproved($this->status, $glass->glassModelId !== null)) {
            throw OfferRefused::glassModelRequired();
        }

        $this->content = $this->content->withGlass($glass);
        $this->activate($now);
    }

    /**
     * FR-09: Pending or Active to Rejected, with a reason (the reason goes to the owner's Journal).
     * RULE-04: a rejection ends the standing approval, so the Offer counts as changed since it; only an
     * admin's approval (or a seller's edit to Pending) takes it on.
     */
    public function reject(string $reason): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeRejected($this->status)) {
            throw OfferRefused::cannotBeRejected();
        }

        if (trim($reason) === '') {
            throw OfferRefused::rejectionReasonRequired();
        }

        $this->status = OfferStatus::Rejected;
        $this->changedSinceApproval = true;
    }

    /**
     * FR-05 to FR-08: saves the edit of the content and both photo lists and says whether anything
     * changed. An admin's edit never changes the status (FR-08). A seller's change of anything but
     * the entered price, private notes and private photos sends an Active, Rejected or Withdrawn Offer
     * to Pending (FR-07) and marks it changed since its approval; that needs FR-06 to hold. A Withdrawn
     * Offer that cannot be resumed (§7: "after editing") goes to Pending on any change of it, or it
     * would stay stuck. Draft and Pending stay as they are.
     */
    public function edit(
        OfferContent $content,
        OfferPhotos $photos,
        int $editorId,
        bool $editorIsAdmin,
        bool $glassModelCanBePicked,
        bool $producerCanBePicked,
    ): bool {
        $this->guardNotInTrash();

        if (! $editorIsAdmin && ! OfferDomainPolicy::canBeEditedBySeller($this->status)) {
            throw OfferRefused::cannotBeEditedWhileReservedOrSold();
        }

        self::guardPicks($this->content, $content, $glassModelCanBePicked, $producerCanBePicked);
        $this->guardPhotos($photos, $editorId);

        $changed = $content != $this->content || $photos != $this->photos;
        $substantive = OfferDomainPolicy::changeNeedsReapproval($this->content, $this->photos, $content, $photos);

        $needsReapproval = ! $editorIsAdmin
            && in_array($this->status, [OfferStatus::Active, OfferStatus::Rejected, OfferStatus::Withdrawn], true)
            && ($substantive || ($changed && $this->status === OfferStatus::Withdrawn && ! $this->canResumeWithoutApproval()));

        if ($needsReapproval) {
            $this->guardCompleteForSending($content, $photos);
        }

        if (! $editorIsAdmin && $substantive && $this->status !== OfferStatus::Draft) {
            $this->changedSinceApproval = true;
        }

        $this->content = $content;
        $this->photos = $photos;

        if ($needsReapproval) {
            $this->status = OfferStatus::Pending;
        }

        return $changed;
    }

    private function activate(CarbonImmutable $now): void
    {
        $this->status = OfferStatus::Active;
        $this->firstApprovedAt ??= $now;
        $this->lastApprovedAt = $now;
        $this->changedSinceApproval = false;
    }

    /** FR-24: Pending, Rejected or Active to Withdrawn (the owner or an admin; the handler checks who). */
    public function withdraw(): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeWithdrawn($this->status, false)) {
            throw OfferRefused::cannotBeWithdrawn();
        }

        $this->status = OfferStatus::Withdrawn;
    }

    /**
     * FR-24, FR-13: "Wznów sprzedaż". An admin's own Offer resumes whenever it has a Glass model, as
     * sending would; any other Offer only when it was approved before and has not changed since (the
     * approval dates stay: nobody approved it again). Otherwise it must be edited and goes to Pending.
     */
    public function resume(bool $ownerIsAdmin, CarbonImmutable $now): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeResumed($this->status, false)) {
            throw OfferRefused::cannotBeResumed();
        }

        if ($ownerIsAdmin && $this->content->glass->glassModelId !== null) {
            $this->activate($now);

            return;
        }

        if (! $this->canResumeWithoutApproval()) {
            throw OfferRefused::cannotBeResumed();
        }

        $this->status = OfferStatus::Active;
    }

    /**
     * FR-16, RULE-05: Active to Reserved. The handler says whether a Reservation is still current, since
     * an Offer has at most one.
     */
    public function reserve(bool $hasCurrentReservation): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeReserved($this->status, false) || $hasCurrentReservation) {
            throw OfferRefused::cannotBeReserved();
        }

        $this->status = OfferStatus::Reserved;
    }

    /** FR-17: Reserved to Active (the handler checks who may, from the Reservation). */
    public function release(): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeReleased($this->status, false)) {
            throw OfferRefused::cannotBeReleased();
        }

        $this->status = OfferStatus::Active;
    }

    /** FR-18: Active or Reserved to Sold; the Sale records the entered price, so the Offer must have one. */
    public function sell(): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeSold($this->status, false)) {
            throw OfferRefused::cannotBeSold();
        }

        if ($this->content->price === null) {
            throw OfferRefused::priceRequiredToSell();
        }

        $this->status = OfferStatus::Sold;
    }

    /** FR-19: Sold to Active (an admin, with a reason on the Sale). */
    public function returnSale(): void
    {
        $this->guardNotInTrash();

        if (! OfferDomainPolicy::canBeReturned($this->status, false)) {
            throw OfferRefused::saleCannotBeReturned();
        }

        $this->status = OfferStatus::Active;
    }

    private function canResumeWithoutApproval(): bool
    {
        return OfferDomainPolicy::resumesWithoutApproval($this->lastApprovedAt !== null, $this->changedSinceApproval);
    }

    /** FR-25: an admin deletes the Offer into the Trash; it keeps its status. */
    public function moveToTrash(CarbonImmutable $now): void
    {
        if (! OfferDomainPolicy::canBeTrashed($this->status, $this->isInTrash())) {
            throw $this->isInTrash() ? OfferRefused::cannotBeTrashed() : OfferRefused::reservedCannotBeTrashed();
        }

        $this->trashedAt = $now;
    }

    /** FR-25: back from the Trash with the status it had. */
    public function restoreFromTrash(): void
    {
        if (! OfferDomainPolicy::canBeRestored($this->isInTrash())) {
            throw OfferRefused::cannotBeRestored();
        }

        $this->trashedAt = null;
    }

    /** FR-26: the handler removes the Offer with what belongs to it only after this passes. */
    public function ensureCanBeDeletedPermanently(): void
    {
        if (! OfferDomainPolicy::canBeDeletedPermanently($this->isInTrash())) {
            throw OfferRefused::cannotBeDeletedPermanently();
        }
    }

    private function guardNotInTrash(): void
    {
        if ($this->isInTrash()) {
            throw OfferRefused::isInTrash();
        }
    }

    private function guardCompleteForSending(OfferContent $content, OfferPhotos $photos): void
    {
        $missing = OfferDomainPolicy::missingForSending($content, $photos);

        if ($missing !== []) {
            throw OfferRefused::missingForSending($missing);
        }
    }

    /**
     * FR-05: replaces both photo lists, order kept. A path the Offer already holds stays whoever
     * saves; a new one must sit in the uploader's own folder for its kind, so nobody can name
     * another user's file, a private one included, as their own photo.
     */
    public function arrangePhotos(OfferPhotos $photos, int $uploaderId): void
    {
        $this->guardPhotos($photos, $uploaderId);

        $this->photos = $photos;
    }

    private function guardPhotos(OfferPhotos $photos, int $uploaderId): void
    {
        if (
            ! OfferDomainPolicy::photosAreFromUploader($photos->public, $this->photos->public, OfferDomainPolicy::publicPhotoDirectory($uploaderId))
            || ! OfferDomainPolicy::photosAreFromUploader($photos->private, $this->photos->private, OfferDomainPolicy::privatePhotoDirectory($uploaderId))
        ) {
            throw OfferRefused::photoIsInvalid();
        }
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

    public function photos(): OfferPhotos
    {
        return $this->photos;
    }

    public function firstApprovedAt(): ?CarbonImmutable
    {
        return $this->firstApprovedAt;
    }

    public function lastApprovedAt(): ?CarbonImmutable
    {
        return $this->lastApprovedAt;
    }

    /** Whether a seller changed more than price and private material since the last approval (FR-24). */
    public function changedSinceApproval(): bool
    {
        return $this->changedSinceApproval;
    }

    public function isInTrash(): bool
    {
        return $this->trashedAt !== null;
    }

    public function trashedAt(): ?CarbonImmutable
    {
        return $this->trashedAt;
    }
}
