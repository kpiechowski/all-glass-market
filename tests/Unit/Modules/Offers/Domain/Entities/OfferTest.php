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
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(glassModelId: 5));

        $offer->revise($this->content(glassModelId: 5, hint: null), false, true);

        $this->assertSame(5, $offer->content()->glass->glassModelId);
    }

    public function test_changing_to_a_glass_model_that_cannot_be_picked_is_refused(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(glassModelId: 5));

        $this->expectException(OfferRefused::class);

        $offer->revise($this->content(glassModelId: 6), false, true);
    }

    public function test_a_producer_that_cannot_be_picked_is_refused_for_a_new_choice_but_kept_when_unchanged(): void
    {
        $offer = Offer::reconstitute(3, OfferNumber::from('AG00000001'), 11, OfferStatus::Draft, $this->content(producerId: 8));

        $offer->revise($this->content(producerId: 8), true, false);
        $this->assertSame(8, $offer->content()->producerId);

        $this->expectException(OfferRefused::class);
        $offer->revise($this->content(producerId: 9), true, false);
    }
}
