<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Policies;

use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use App\Modules\Offers\Domain\ValueObjects\Rating;
use PHPUnit\Framework\TestCase;

class OfferDomainPolicyTest extends TestCase
{
    public function test_a_rating_applies_to_used_and_outlet_only(): void
    {
        $this->assertTrue(OfferDomainPolicy::ratingApplies(OfferCondition::Used));
        $this->assertTrue(OfferDomainPolicy::ratingApplies(OfferCondition::Outlet));
        $this->assertFalse(OfferDomainPolicy::ratingApplies(OfferCondition::New));
        $this->assertFalse(OfferDomainPolicy::ratingApplies(null));
    }

    public function test_a_new_piece_is_shown_with_five_stars_and_others_with_their_own(): void
    {
        $this->assertSame(5, OfferDomainPolicy::displayedStars(OfferCondition::New, null));
        $this->assertSame(3, OfferDomainPolicy::displayedStars(OfferCondition::Used, 3));
        $this->assertNull(OfferDomainPolicy::displayedStars(OfferCondition::Outlet, null));
        $this->assertNull(OfferDomainPolicy::displayedStars(null, null));
    }

    public function test_the_owner_and_whoever_manages_all_offers_may_see_and_change_an_offer(): void
    {
        $this->assertTrue(OfferDomainPolicy::canBeSeenBy(ownerId: 5, userId: 5, managesAll: false));
        $this->assertTrue(OfferDomainPolicy::canBeSeenBy(ownerId: 5, userId: 9, managesAll: true));
        $this->assertFalse(OfferDomainPolicy::canBeSeenBy(ownerId: 5, userId: 9, managesAll: false));
    }

    public function test_a_new_photo_must_sit_in_the_uploaders_folder_and_an_existing_one_may_stay(): void
    {
        $this->assertTrue(OfferDomainPolicy::photosAreFromUploader(['offers/public/3/a.jpg'], [], 'offers/public/3'));
        $this->assertTrue(OfferDomainPolicy::photosAreFromUploader(['offers/public/9/old.jpg'], ['offers/public/9/old.jpg'], 'offers/public/3'));
        $this->assertFalse(OfferDomainPolicy::photosAreFromUploader(['offers/public/9/other.jpg'], ['offers/public/9/old.jpg'], 'offers/public/3'));
        $this->assertFalse(OfferDomainPolicy::photosAreFromUploader(['offers/public/30/a.jpg'], [], 'offers/public/3'));
    }

    public function test_the_upload_folders_belong_to_the_uploader_and_the_kind(): void
    {
        $this->assertSame('offers/public/3', OfferDomainPolicy::publicPhotoDirectory(3));
        $this->assertSame('offers/private/3', OfferDomainPolicy::privatePhotoDirectory(3));
    }

    private function content(
        ?int $glassModelId = 5,
        ?string $hint = null,
        ?OfferCondition $condition = OfferCondition::Used,
        ?int $rating = 3,
        ?int $priceGrosze = 15000,
        ?int $producerId = 1,
        ?int $year = 2020,
        ?string $description = 'Opis',
        ?string $notes = 'Notatka',
    ): OfferContent {
        return OfferContent::from(
            GlassReference::from($glassModelId, $hint),
            $producerId,
            $year,
            $condition,
            $rating === null ? null : Rating::from($rating),
            $description,
            $notes,
            $priceGrosze === null ? null : EnteredPrice::fromGrosze($priceGrosze),
        );
    }

    private function photos(array $public = ['offers/public/1/a.jpg'], array $private = []): OfferPhotos
    {
        return OfferPhotos::from($public, $private);
    }

    public function test_a_complete_offer_misses_nothing_for_sending(): void
    {
        $this->assertSame([], OfferDomainPolicy::missingForSending($this->content(), $this->photos()));
        $this->assertSame([], OfferDomainPolicy::missingForSending($this->content(glassModelId: null, hint: 'VW Golf'), $this->photos()));
    }

    public function test_sending_names_every_missing_field(): void
    {
        $missing = OfferDomainPolicy::missingForSending(
            $this->content(glassModelId: null, condition: null, rating: null, priceGrosze: 99),
            $this->photos(public: []),
        );

        $this->assertSame(['glass', 'price', 'condition', 'public_photos'], $missing);
    }

    public function test_a_used_or_outlet_offer_without_a_rating_misses_the_rating(): void
    {
        $this->assertSame(['rating'], OfferDomainPolicy::missingForSending($this->content(rating: null), $this->photos()));
        $this->assertSame(['rating'], OfferDomainPolicy::missingForSending($this->content(condition: OfferCondition::Outlet, rating: null), $this->photos()));
        $this->assertSame([], OfferDomainPolicy::missingForSending($this->content(condition: OfferCondition::New, rating: null), $this->photos()));
    }

    public function test_a_price_of_one_zloty_is_enough_and_none_is_missing(): void
    {
        $this->assertSame([], OfferDomainPolicy::missingForSending($this->content(priceGrosze: 100), $this->photos()));
        $this->assertSame(['price'], OfferDomainPolicy::missingForSending($this->content(priceGrosze: null), $this->photos()));
    }

    public function test_only_a_draft_or_rejected_offer_can_be_sent(): void
    {
        $this->assertTrue(OfferDomainPolicy::canBeSent(OfferStatus::Draft));
        $this->assertTrue(OfferDomainPolicy::canBeSent(OfferStatus::Rejected));
        $this->assertFalse(OfferDomainPolicy::canBeSent(OfferStatus::Pending));
        $this->assertFalse(OfferDomainPolicy::canBeSent(OfferStatus::Active));
    }

    public function test_a_pending_or_rejected_offer_with_a_glass_model_can_be_approved(): void
    {
        $this->assertTrue(OfferDomainPolicy::canBeApproved(OfferStatus::Pending, true));
        $this->assertTrue(OfferDomainPolicy::canBeApproved(OfferStatus::Rejected, true));
        $this->assertFalse(OfferDomainPolicy::canBeApproved(OfferStatus::Pending, false));
        $this->assertFalse(OfferDomainPolicy::canBeApproved(OfferStatus::Draft, true));
        $this->assertFalse(OfferDomainPolicy::canBeApproved(OfferStatus::Active, true));
    }

    public function test_approval_applies_to_pending_and_rejected_whatever_the_glass_model(): void
    {
        $this->assertTrue(OfferDomainPolicy::approvalApplies(OfferStatus::Pending));
        $this->assertTrue(OfferDomainPolicy::approvalApplies(OfferStatus::Rejected));
        $this->assertFalse(OfferDomainPolicy::approvalApplies(OfferStatus::Draft));
        $this->assertFalse(OfferDomainPolicy::approvalApplies(OfferStatus::Active));
    }

    public function test_a_pending_or_active_offer_can_be_rejected(): void
    {
        $this->assertTrue(OfferDomainPolicy::canBeRejected(OfferStatus::Pending));
        $this->assertTrue(OfferDomainPolicy::canBeRejected(OfferStatus::Active));
        $this->assertFalse(OfferDomainPolicy::canBeRejected(OfferStatus::Draft));
        $this->assertFalse(OfferDomainPolicy::canBeRejected(OfferStatus::Rejected));
    }

    public function test_price_and_private_material_changes_need_no_reapproval(): void
    {
        $before = $this->content();

        $this->assertFalse(OfferDomainPolicy::changeNeedsReapproval(
            $before,
            $this->photos(private: ['offers/private/1/x.jpg']),
            $this->content(priceGrosze: 99900, notes: 'Inna notatka'),
            $this->photos(private: ['offers/private/1/y.jpg', 'offers/private/1/z.jpg']),
        ));
    }

    public function test_every_other_change_needs_reapproval(): void
    {
        $before = $this->content();
        $photos = $this->photos();

        $changed = [
            'glass model' => $this->content(glassModelId: 6),
            'hint instead of model' => $this->content(glassModelId: null, hint: 'kod'),
            'producer' => $this->content(producerId: 2),
            'year' => $this->content(year: 2021),
            'condition' => $this->content(condition: OfferCondition::Outlet),
            'rating' => $this->content(rating: 4),
            'description' => $this->content(description: 'Nowy opis'),
        ];

        foreach ($changed as $what => $after) {
            $this->assertTrue(OfferDomainPolicy::changeNeedsReapproval($before, $photos, $after, $photos), $what);
        }
    }

    public function test_a_public_photo_change_or_a_reorder_needs_reapproval(): void
    {
        $content = $this->content();
        $two = $this->photos(['offers/public/1/a.jpg', 'offers/public/1/b.jpg']);

        $this->assertTrue(OfferDomainPolicy::changeNeedsReapproval($content, $two, $content, $this->photos(['offers/public/1/b.jpg', 'offers/public/1/a.jpg'])));
        $this->assertTrue(OfferDomainPolicy::changeNeedsReapproval($content, $two, $content, $this->photos(['offers/public/1/a.jpg'])));
        $this->assertFalse(OfferDomainPolicy::changeNeedsReapproval($content, $two, $content, $two));
    }

    public function test_only_pending_rejected_and_active_offers_can_be_withdrawn(): void
    {
        foreach ([OfferStatus::Pending, OfferStatus::Rejected, OfferStatus::Active] as $status) {
            $this->assertTrue(OfferDomainPolicy::canBeWithdrawn($status, false), $status->value);
            $this->assertFalse(OfferDomainPolicy::canBeWithdrawn($status, true), $status->value.' in the Trash');
        }

        $this->assertFalse(OfferDomainPolicy::canBeWithdrawn(OfferStatus::Draft, false));
        $this->assertFalse(OfferDomainPolicy::canBeWithdrawn(OfferStatus::Withdrawn, false));
    }

    public function test_only_a_withdrawn_offer_outside_the_trash_can_be_resumed(): void
    {
        $this->assertTrue(OfferDomainPolicy::canBeResumed(OfferStatus::Withdrawn, false));
        $this->assertFalse(OfferDomainPolicy::canBeResumed(OfferStatus::Withdrawn, true));
        $this->assertFalse(OfferDomainPolicy::canBeResumed(OfferStatus::Active, false));
    }

    public function test_a_withdrawn_offer_resumes_only_when_approved_before_and_unchanged_since(): void
    {
        $this->assertTrue(OfferDomainPolicy::resumesWithoutApproval(true, false));
        $this->assertFalse(OfferDomainPolicy::resumesWithoutApproval(true, true));
        $this->assertFalse(OfferDomainPolicy::resumesWithoutApproval(false, false));
    }

    public function test_the_trash_takes_any_offer_but_a_reserved_one_and_gives_back_only_what_it_holds(): void
    {
        foreach (OfferStatus::cases() as $status) {
            $this->assertSame($status !== OfferStatus::Reserved, OfferDomainPolicy::canBeTrashed($status, false), $status->value);
            $this->assertFalse(OfferDomainPolicy::canBeTrashed($status, true), $status->value);
        }

        $this->assertTrue(OfferDomainPolicy::canBeRestored(true));
        $this->assertFalse(OfferDomainPolicy::canBeRestored(false));
        $this->assertTrue(OfferDomainPolicy::canBeDeletedPermanently(true));
        $this->assertFalse(OfferDomainPolicy::canBeDeletedPermanently(false));
    }

    public function test_reserving_needs_an_active_offer_and_releasing_a_reserved_one_outside_the_trash(): void
    {
        foreach (OfferStatus::cases() as $status) {
            $this->assertSame($status === OfferStatus::Active, OfferDomainPolicy::canBeReserved($status, false), $status->value);
            $this->assertSame($status === OfferStatus::Reserved, OfferDomainPolicy::canBeReleased($status, false), $status->value);
            $this->assertSame($status === OfferStatus::Sold, OfferDomainPolicy::canBeReturned($status, false), $status->value);
            $this->assertSame(in_array($status, [OfferStatus::Active, OfferStatus::Reserved], true), OfferDomainPolicy::canBeSold($status, false), $status->value);
        }

        $this->assertFalse(OfferDomainPolicy::canBeReserved(OfferStatus::Active, true));
        $this->assertFalse(OfferDomainPolicy::canBeSold(OfferStatus::Active, true));
        $this->assertFalse(OfferDomainPolicy::canBeReturned(OfferStatus::Sold, true));
    }

    public function test_a_reserved_offer_cannot_be_trashed_but_a_sold_one_can(): void
    {
        $this->assertFalse(OfferDomainPolicy::canBeTrashed(OfferStatus::Reserved, false));
        $this->assertTrue(OfferDomainPolicy::canBeTrashed(OfferStatus::Sold, false));
    }

    public function test_an_admins_reservation_is_handled_by_an_admin_only(): void
    {
        $this->assertTrue(OfferDomainPolicy::reservationCanBeHandledBy(true, true));
        $this->assertFalse(OfferDomainPolicy::reservationCanBeHandledBy(true, false));
        $this->assertTrue(OfferDomainPolicy::reservationCanBeHandledBy(false, false));
        $this->assertTrue(OfferDomainPolicy::reservationCanBeHandledBy(false, true));
    }

    public function test_a_seller_edits_neither_a_reserved_nor_a_sold_offer(): void
    {
        $this->assertFalse(OfferDomainPolicy::canBeEditedBySeller(OfferStatus::Reserved));
        $this->assertFalse(OfferDomainPolicy::canBeEditedBySeller(OfferStatus::Sold));
        $this->assertTrue(OfferDomainPolicy::canBeEditedBySeller(OfferStatus::Active));
    }
}
