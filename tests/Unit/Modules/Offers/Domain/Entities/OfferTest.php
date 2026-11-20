<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
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

    public function test_revising_keeps_the_owner_the_number_and_the_status(): void
    {
        $offer = $this->draft();

        $offer->revise($this->content(glassModelId: 5), true, true);

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

        $offer->revise($this->content(glassModelId: 5, hint: null), false, true);

        $this->assertSame(5, $offer->content()->glass->glassModelId);
    }

    public function test_changing_to_a_glass_model_that_cannot_be_picked_is_refused(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(glassModelId: 5), OfferPhotos::none());

        $this->expectException(OfferRefused::class);

        $offer->revise($this->content(glassModelId: 6), false, true);
    }

    public function test_a_producer_that_cannot_be_picked_is_refused_for_a_new_choice_but_kept_when_unchanged(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(producerId: 8), OfferPhotos::none());

        $offer->revise($this->content(producerId: 8), true, false);
        $this->assertSame(8, $offer->content()->producerId);

        $this->expectException(OfferRefused::class);
        $offer->revise($this->content(producerId: 9), true, false);
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
}
