<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use App\Modules\Offers\Domain\ValueObjects\Rating;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class OfferTest extends TestCase
{
    private function content(?int $glassModelId = null, ?string $hint = null, ?int $producerId = null): OfferContent
    {
        return OfferContent::from(GlassReference::from($glassModelId, $hint), $producerId, 2020, OfferCondition::New, null, 'Opis', 'Notatka', null);
    }

    private function draft(?OfferContent $content = null): Offer
    {
        return Offer::draft(OfferNumber::from('AG00000001'), 11, $content ?? $this->content(), true, true);
    }

    public function test_a_draft_belongs_to_its_creator_and_has_a_number_and_no_id_yet(): void
    {
        $offer = $this->draft();

        $this->assertNull($offer->id());
        $this->assertSame(11, $offer->ownerId());
        $this->assertSame('AG00000001', $offer->number()->value);
        $this->assertSame(OfferStatus::Draft, $offer->status());
    }

    public function test_editing_a_draft_keeps_the_owner_the_number_and_the_status(): void
    {
        $offer = $this->draft();

        $offer->edit($this->content(glassModelId: 5), $offer->photos(), 11, false, true, true);

        $this->assertSame(5, $offer->content()->glass->glassModelId);
        $this->assertSame(11, $offer->ownerId());
        $this->assertSame('AG00000001', $offer->number()->value);
        $this->assertSame(OfferStatus::Draft, $offer->status());
    }

    public function test_a_glass_model_that_cannot_be_picked_is_refused_for_a_new_choice(): void
    {
        try {
            Offer::draft(OfferNumber::from('AG00000001'), 11, $this->content(glassModelId: 5), false, true);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.glass_model_cannot_be_picked', $refusal->translationKey);
        }
    }

    public function test_a_withdrawn_glass_model_the_offer_already_has_survives_a_revision(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(glassModelId: 5), OfferPhotos::none());

        $offer->edit($this->content(glassModelId: 5, hint: null), $offer->photos(), 11, false, false, true);

        $this->assertSame(5, $offer->content()->glass->glassModelId);
    }

    public function test_changing_to_a_glass_model_that_cannot_be_picked_is_refused(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(glassModelId: 5), OfferPhotos::none());

        $this->expectException(OfferRefused::class);

        $offer->edit($this->content(glassModelId: 6), $offer->photos(), 11, false, false, true);
    }

    public function test_a_producer_that_cannot_be_picked_is_refused_for_a_new_choice_but_kept_when_unchanged(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(producerId: 8), OfferPhotos::none());

        $offer->edit($this->content(producerId: 8), $offer->photos(), 11, false, true, false);
        $this->assertSame(8, $offer->content()->producerId);

        $this->expectException(OfferRefused::class);
        $offer->edit($this->content(producerId: 9), $offer->photos(), 11, false, true, false);
    }

    public function test_a_new_draft_has_no_photos(): void
    {
        $this->assertSame([], $this->draft()->photos()->public);
        $this->assertSame([], $this->draft()->photos()->private);
    }

    public function test_the_uploader_arranges_photos_in_their_own_folders(): void
    {
        $offer = $this->draft();

        $offer->arrangePhotos(OfferPhotos::from(['offers/public/11/b.jpg', 'offers/public/11/a.jpg'], ['offers/private/11/x.jpg']), 11);

        $this->assertSame(['offers/public/11/b.jpg', 'offers/public/11/a.jpg'], $offer->photos()->public);
        $this->assertSame(['offers/private/11/x.jpg'], $offer->photos()->private);
    }

    public function test_a_photo_already_on_the_offer_stays_when_an_admin_saves_it(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(), OfferPhotos::from(['offers/public/11/a.jpg'], ['offers/private/11/x.jpg']));

        $offer->arrangePhotos(OfferPhotos::from(['offers/public/11/a.jpg', 'offers/public/99/new.jpg'], ['offers/private/11/x.jpg']), 99);

        $this->assertSame(['offers/public/11/a.jpg', 'offers/public/99/new.jpg'], $offer->photos()->public);
    }

    public function test_a_new_photo_from_another_users_folder_is_refused(): void
    {
        $offer = $this->draft();

        $this->expectException(OfferRefused::class);

        $offer->arrangePhotos(OfferPhotos::from([], ['offers/private/12/secret.jpg']), 11);
    }

    public function test_a_private_path_cannot_be_added_as_a_public_photo(): void
    {
        $offer = $this->draft();

        $this->expectException(OfferRefused::class);

        $offer->arrangePhotos(OfferPhotos::from(['offers/private/11/x.jpg'], []), 11);
    }

    private const string PHOTO = 'offers/public/11/a.jpg';

    /** A complete, sendable content: model 5, used (3 stars), 150 zł. */
    private function complete(?int $glassModelId = 5, ?string $hint = null, int $priceGrosze = 15000, ?string $description = 'Opis'): OfferContent
    {
        return OfferContent::from(
            GlassReference::from($glassModelId, $hint),
            3,
            2020,
            OfferCondition::Used,
            Rating::from(3),
            $description,
            'Notatka',
            EnteredPrice::fromGrosze($priceGrosze),
        );
    }

    private function offerIn(OfferStatus $status, ?OfferContent $content = null, ?OfferPhotos $photos = null): Offer
    {
        return Offer::reconstitute(
            3,
            OfferNumber::from('AG00000001'),
            11,
            $status,
            $content ?? $this->complete(),
            $photos ?? OfferPhotos::from([self::PHOTO], ['offers/private/11/x.jpg']),
        );
    }

    private function emptyContent(): OfferContent
    {
        return OfferContent::from(GlassReference::from(null, null), null, null, null, null, null, null, null);
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-20 12:00:00');
    }

    private function refusalKey(callable $action): string
    {
        try {
            $action();
        } catch (OfferRefused $refusal) {
            return $refusal->translationKey;
        }

        $this->fail('Expected a refusal.');
    }

    public function test_a_seller_sends_a_complete_draft_to_pending(): void
    {
        $offer = $this->offerIn(OfferStatus::Draft);

        $offer->send(false, $this->now());

        $this->assertSame(OfferStatus::Pending, $offer->status());
        $this->assertNull($offer->firstApprovedAt());
    }

    public function test_sending_refuses_an_incomplete_draft(): void
    {
        $offer = $this->offerIn(OfferStatus::Draft, $this->emptyContent(), OfferPhotos::none());

        $this->assertSame('offers::exceptions.missing_for_sending', $this->refusalKey(fn () => $offer->send(false, $this->now())));
        $this->assertSame(OfferStatus::Draft, $offer->status());
    }

    public function test_a_rejected_offer_is_sent_again_to_pending(): void
    {
        $offer = $this->offerIn(OfferStatus::Rejected);

        $offer->send(false, $this->now());

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_pending_and_active_offers_cannot_be_sent(): void
    {
        foreach ([OfferStatus::Pending, OfferStatus::Active] as $status) {
            $offer = $this->offerIn($status);

            $this->assertSame('offers::exceptions.cannot_be_sent', $this->refusalKey(fn () => $offer->send(false, $this->now())));
        }
    }

    public function test_an_admins_own_offer_with_a_glass_model_goes_active_on_sending_and_records_the_approval(): void
    {
        $offer = $this->offerIn(OfferStatus::Draft);

        $offer->send(true, $this->now());

        $this->assertSame(OfferStatus::Active, $offer->status());
        $this->assertEquals($this->now(), $offer->firstApprovedAt());
        $this->assertEquals($this->now(), $offer->lastApprovedAt());
    }

    public function test_an_admins_own_offer_with_only_a_hint_goes_pending_on_sending(): void
    {
        $offer = $this->offerIn(OfferStatus::Draft, $this->complete(glassModelId: null, hint: 'VW Golf'));

        $offer->send(true, $this->now());

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_an_admin_sending_an_incomplete_offer_is_refused_too(): void
    {
        $offer = $this->offerIn(OfferStatus::Draft, $this->emptyContent(), OfferPhotos::none());

        $this->assertSame('offers::exceptions.missing_for_sending', $this->refusalKey(fn () => $offer->send(true, $this->now())));
    }

    public function test_a_pending_or_rejected_offer_with_a_glass_model_is_approved(): void
    {
        foreach ([OfferStatus::Pending, OfferStatus::Rejected] as $status) {
            $offer = $this->offerIn($status);

            $offer->approve($this->now());

            $this->assertSame(OfferStatus::Active, $offer->status());
        }
    }

    public function test_approving_sets_the_first_approval_once_and_the_last_every_time(): void
    {
        $first = CarbonImmutable::parse('2026-01-01 10:00:00');
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Pending, $this->complete(), OfferPhotos::from([self::PHOTO], []), $first, $first);

        $offer->approve($this->now());

        $this->assertEquals($first, $offer->firstApprovedAt());
        $this->assertEquals($this->now(), $offer->lastApprovedAt());
    }

    public function test_approving_without_a_glass_model_is_refused(): void
    {
        $offer = $this->offerIn(OfferStatus::Pending, $this->complete(glassModelId: null, hint: 'VW Golf'));

        $this->assertSame('offers::exceptions.glass_model_required', $this->refusalKey(fn () => $offer->approve($this->now())));
        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_approving_with_a_chosen_glass_model_selects_it_and_drops_the_hint(): void
    {
        $offer = $this->offerIn(OfferStatus::Pending, $this->complete(glassModelId: null, hint: 'VW Golf'));

        $offer->approve($this->now(), 9, true);

        $this->assertSame(OfferStatus::Active, $offer->status());
        $this->assertSame(9, $offer->content()->glass->glassModelId);
        $this->assertNull($offer->content()->glass->identificationHint);
    }

    public function test_a_chosen_glass_model_that_cannot_be_picked_is_refused(): void
    {
        $offer = $this->offerIn(OfferStatus::Pending, $this->complete(glassModelId: null, hint: 'VW Golf'));

        $this->assertSame('offers::exceptions.glass_model_cannot_be_picked', $this->refusalKey(fn () => $offer->approve($this->now(), 9, false)));
        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_draft_and_active_offers_cannot_be_approved(): void
    {
        foreach ([OfferStatus::Draft, OfferStatus::Active] as $status) {
            $offer = $this->offerIn($status);

            $this->assertSame('offers::exceptions.cannot_be_approved', $this->refusalKey(fn () => $offer->approve($this->now())));
        }
    }

    public function test_a_pending_or_active_offer_is_rejected_with_a_reason(): void
    {
        foreach ([OfferStatus::Pending, OfferStatus::Active] as $status) {
            $offer = $this->offerIn($status);

            $offer->reject('Złe zdjęcia');

            $this->assertSame(OfferStatus::Rejected, $offer->status());
        }
    }

    public function test_rejecting_needs_a_reason(): void
    {
        $offer = $this->offerIn(OfferStatus::Pending);

        $this->assertSame('offers::exceptions.rejection_reason_required', $this->refusalKey(fn () => $offer->reject("  \n ")));
        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_draft_and_rejected_offers_cannot_be_rejected(): void
    {
        foreach ([OfferStatus::Draft, OfferStatus::Rejected] as $status) {
            $offer = $this->offerIn($status);

            $this->assertSame('offers::exceptions.cannot_be_rejected', $this->refusalKey(fn () => $offer->reject('Powód')));
        }
    }

    public function test_a_sellers_price_only_edit_keeps_an_active_offer_active(): void
    {
        $offer = $this->offerIn(OfferStatus::Active);

        $changed = $offer->edit($this->complete(priceGrosze: 99900), $offer->photos(), 11, false, true, true);

        $this->assertTrue($changed);
        $this->assertSame(OfferStatus::Active, $offer->status());
        $this->assertSame(99900, $offer->content()->price->grosze);
    }

    public function test_a_sellers_private_notes_and_private_photos_edit_keeps_the_status(): void
    {
        foreach ([OfferStatus::Active, OfferStatus::Rejected] as $status) {
            $offer = $this->offerIn($status);
            $content = OfferContent::from($offer->content()->glass, 3, 2020, OfferCondition::Used, Rating::from(3), 'Opis', 'Zupełnie inne notatki', $offer->content()->price);

            $offer->edit($content, OfferPhotos::from([self::PHOTO], []), 11, false, true, true);

            $this->assertSame($status, $offer->status());
            $this->assertSame([], $offer->photos()->private);
        }
    }

    public function test_a_sellers_description_edit_sends_an_active_offer_to_pending(): void
    {
        $offer = $this->offerIn(OfferStatus::Active);

        $offer->edit($this->complete(description: 'Inny opis'), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_a_sellers_public_photo_edit_sends_an_active_offer_to_pending(): void
    {
        $offer = $this->offerIn(OfferStatus::Active);

        $offer->edit($offer->content(), OfferPhotos::from([self::PHOTO, 'offers/public/11/b.jpg'], ['offers/private/11/x.jpg']), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_reordering_public_photos_sends_an_active_offer_to_pending(): void
    {
        $offer = $this->offerIn(OfferStatus::Active, null, OfferPhotos::from([self::PHOTO, 'offers/public/11/b.jpg'], []));

        $offer->edit($offer->content(), OfferPhotos::from(['offers/public/11/b.jpg', self::PHOTO], []), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_a_sellers_other_change_sends_a_rejected_offer_to_pending(): void
    {
        $offer = $this->offerIn(OfferStatus::Rejected);

        $offer->edit($this->complete(description: 'Poprawiony opis'), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_a_save_with_nothing_changed_keeps_the_status_and_reports_no_change(): void
    {
        $offer = $this->offerIn(OfferStatus::Active);

        $changed = $offer->edit($offer->content(), $offer->photos(), 11, false, true, true);

        $this->assertFalse($changed);
        $this->assertSame(OfferStatus::Active, $offer->status());
    }

    public function test_a_sellers_edit_of_a_pending_offer_keeps_it_pending(): void
    {
        $offer = $this->offerIn(OfferStatus::Pending);

        $offer->edit($this->complete(description: 'Inny opis'), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_a_sellers_edit_of_a_draft_keeps_it_a_draft(): void
    {
        $offer = $this->offerIn(OfferStatus::Draft);

        $offer->edit($this->complete(description: 'Inny opis'), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Draft, $offer->status());
    }

    public function test_a_sellers_edit_that_would_leave_an_active_offer_incomplete_is_refused(): void
    {
        $offer = $this->offerIn(OfferStatus::Active);

        $this->assertSame('offers::exceptions.missing_for_sending', $this->refusalKey(fn () => $offer->edit($offer->content(), OfferPhotos::none(), 11, false, true, true)));
        $this->assertSame(OfferStatus::Active, $offer->status());
        $this->assertSame([self::PHOTO], $offer->photos()->public);
    }

    public function test_an_admin_edit_never_changes_the_status(): void
    {
        foreach ([OfferStatus::Draft, OfferStatus::Pending, OfferStatus::Rejected, OfferStatus::Active] as $status) {
            $offer = $this->offerIn($status);

            $changed = $offer->edit(
                $this->complete(glassModelId: 6, description: 'Opis admina'),
                OfferPhotos::from([self::PHOTO, 'offers/public/99/b.jpg'], []),
                99,
                true,
                true,
                true,
            );

            $this->assertTrue($changed);
            $this->assertSame($status, $offer->status());
        }
    }

    public function test_an_admin_may_leave_an_offer_incomplete_without_changing_its_status(): void
    {
        $offer = $this->offerIn(OfferStatus::Active);

        $offer->edit($offer->content(), OfferPhotos::none(), 99, true, true, true);

        $this->assertSame(OfferStatus::Active, $offer->status());
    }

    private function approvedOffer(OfferStatus $status, bool $changedSinceApproval = false, int $ownerId = 11, ?OfferContent $content = null): Offer
    {
        return Offer::reconstitute(
            3,
            OfferNumber::from('AG00000001'),
            $ownerId,
            $status,
            $content ?? $this->complete(),
            OfferPhotos::from([self::PHOTO], []),
            $this->now(),
            $this->now(),
            $changedSinceApproval,
        );
    }

    public function test_pending_rejected_and_active_offers_are_withdrawn(): void
    {
        foreach ([OfferStatus::Pending, OfferStatus::Rejected, OfferStatus::Active] as $status) {
            $offer = $this->offerIn($status);

            $offer->withdraw();

            $this->assertSame(OfferStatus::Withdrawn, $offer->status());
        }
    }

    public function test_a_draft_or_withdrawn_offer_cannot_be_withdrawn(): void
    {
        foreach ([OfferStatus::Draft, OfferStatus::Withdrawn] as $status) {
            $this->assertSame('offers::exceptions.cannot_be_withdrawn', $this->refusalKey(fn () => $this->offerIn($status)->withdraw()));
        }
    }

    public function test_an_unchanged_approved_withdrawn_offer_resumes_to_active_without_a_new_approval(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Withdrawn);

        $offer->resume(false, $this->now()->addDay());

        $this->assertSame(OfferStatus::Active, $offer->status());
        $this->assertEquals($this->now(), $offer->lastApprovedAt());
    }

    public function test_a_changed_or_never_approved_withdrawn_offer_cannot_resume(): void
    {
        $changed = $this->approvedOffer(OfferStatus::Withdrawn, changedSinceApproval: true);
        $never = $this->offerIn(OfferStatus::Withdrawn);

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(fn () => $changed->resume(false, $this->now())));
        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(fn () => $never->resume(false, $this->now())));
        $this->assertSame(OfferStatus::Withdrawn, $changed->status());
    }

    public function test_an_offer_that_is_not_withdrawn_cannot_resume(): void
    {
        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(fn () => $this->approvedOffer(OfferStatus::Active)->resume(false, $this->now())));
    }

    public function test_a_rejection_ends_the_standing_approval_so_a_withdrawn_offer_cannot_resume_until_edited(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Active);

        $offer->reject('Zdjęcia');
        $offer->withdraw();

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(fn () => $offer->resume(false, $this->now())));
        $this->assertSame(OfferStatus::Withdrawn, $offer->status());

        $offer->edit($this->complete(description: 'Nowy opis'), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_an_admins_own_withdrawn_offer_resumes_whenever_it_has_a_glass_model(): void
    {
        $offer = $this->offerIn(OfferStatus::Withdrawn);

        $offer->resume(true, $this->now());

        $this->assertSame(OfferStatus::Active, $offer->status());
        $this->assertEquals($this->now(), $offer->lastApprovedAt());
    }

    public function test_an_admins_own_withdrawn_offer_with_only_a_hint_cannot_resume(): void
    {
        $offer = $this->offerIn(OfferStatus::Withdrawn, $this->complete(glassModelId: null, hint: 'kod'));

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(fn () => $offer->resume(true, $this->now())));
    }

    public function test_a_sellers_substantive_edit_marks_the_offer_changed_since_approval(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Active);

        $offer->edit($this->complete(description: 'Inny opis'), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
        $this->assertTrue($offer->changedSinceApproval());
    }

    public function test_a_price_only_edit_and_an_admin_edit_do_not_mark_the_offer_changed(): void
    {
        $byPrice = $this->approvedOffer(OfferStatus::Active);
        $byAdmin = $this->approvedOffer(OfferStatus::Active);

        $byPrice->edit($this->complete(priceGrosze: 20000), $byPrice->photos(), 11, false, true, true);
        $byAdmin->edit($this->complete(description: 'Opis admina'), $byAdmin->photos(), 99, true, true, true);

        $this->assertFalse($byPrice->changedSinceApproval());
        $this->assertFalse($byAdmin->changedSinceApproval());
    }

    public function test_approving_clears_the_changed_mark(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Pending, changedSinceApproval: true);

        $offer->approve($this->now());

        $this->assertFalse($offer->changedSinceApproval());
    }

    public function test_a_sellers_substantive_edit_of_a_withdrawn_offer_sends_it_to_pending(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Withdrawn);

        $offer->edit($this->complete(description: 'Inny opis'), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_a_sellers_price_edit_keeps_a_resumable_withdrawn_offer_withdrawn(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Withdrawn);

        $offer->edit($this->complete(priceGrosze: 20000), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Withdrawn, $offer->status());
    }

    public function test_any_edit_of_a_withdrawn_offer_that_cannot_resume_sends_it_to_pending(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Withdrawn, changedSinceApproval: true);

        $offer->edit($this->complete(priceGrosze: 20000), $offer->photos(), 11, false, true, true);

        $this->assertSame(OfferStatus::Pending, $offer->status());
    }

    public function test_an_admins_edit_never_changes_a_withdrawn_status(): void
    {
        $offer = $this->approvedOffer(OfferStatus::Withdrawn);

        $offer->edit($this->complete(description: 'Opis admina'), $offer->photos(), 99, true, true, true);

        $this->assertSame(OfferStatus::Withdrawn, $offer->status());
    }

    public function test_an_offer_goes_to_the_trash_keeping_its_status_and_comes_back_with_it(): void
    {
        foreach (OfferStatus::cases() as $status) {
            $offer = $this->offerIn($status);

            $offer->moveToTrash($this->now());

            $this->assertTrue($offer->isInTrash());
            $this->assertEquals($this->now(), $offer->trashedAt());
            $this->assertSame($status, $offer->status());

            $offer->restoreFromTrash();

            $this->assertFalse($offer->isInTrash());
            $this->assertNull($offer->trashedAt());
            $this->assertSame($status, $offer->status());
        }
    }

    public function test_an_offer_in_the_trash_cannot_be_trashed_again_and_one_outside_cannot_be_restored(): void
    {
        $trashed = $this->offerIn(OfferStatus::Active);
        $trashed->moveToTrash($this->now());

        $this->assertSame('offers::exceptions.cannot_be_trashed', $this->refusalKey(fn () => $trashed->moveToTrash($this->now())));
        $this->assertSame('offers::exceptions.cannot_be_restored', $this->refusalKey(fn () => $this->offerIn(OfferStatus::Active)->restoreFromTrash()));
    }

    public function test_permanent_deletion_is_allowed_from_the_trash_only(): void
    {
        $offer = $this->offerIn(OfferStatus::Active);

        $this->assertSame('offers::exceptions.cannot_be_deleted_permanently', $this->refusalKey(fn () => $offer->ensureCanBeDeletedPermanently()));

        $offer->moveToTrash($this->now());
        $offer->ensureCanBeDeletedPermanently();

        $this->assertTrue($offer->isInTrash());
    }

    public function test_an_offer_in_the_trash_cannot_be_changed(): void
    {
        $content = $this->complete(description: 'Inny opis');

        foreach ([
            'withdraw' => fn (Offer $offer) => $offer->withdraw(),
            'edit' => fn (Offer $offer) => $offer->edit($content, $offer->photos(), 99, true, true, true),
            'approve' => fn (Offer $offer) => $offer->approve($this->now()),
            'reject' => fn (Offer $offer) => $offer->reject('Powód'),
            'send' => fn (Offer $offer) => $offer->send(false, $this->now()),
            'resume' => fn (Offer $offer) => $offer->resume(true, $this->now()),
        ] as $what => $action) {
            $offer = $this->offerIn(OfferStatus::Pending);
            $offer->moveToTrash($this->now());

            $this->assertSame('offers::exceptions.is_in_trash', $this->refusalKey(fn () => $action($offer)), $what);
        }
    }
}
