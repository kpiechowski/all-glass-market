<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\Rating;
use PHPUnit\Framework\TestCase;

/** FR-04, FR-06: a Draft takes any content, but a rating belongs to a used or outlet piece only. */
class OfferContentTest extends TestCase
{
    private function content(?OfferCondition $condition, ?Rating $rating, ?int $year = null): OfferContent
    {
        return OfferContent::from(GlassReference::from(null, null), null, $year, $condition, $rating, null, null, null);
    }

    public function test_an_empty_content_is_allowed(): void
    {
        $content = $this->content(null, null);

        $this->assertNull($content->condition);
        $this->assertNull($content->rating);
    }

    public function test_a_used_or_outlet_piece_may_have_a_rating(): void
    {
        $this->assertSame(4, $this->content(OfferCondition::Used, Rating::from(4))->rating?->stars);
        $this->assertSame(2, $this->content(OfferCondition::Outlet, Rating::from(2))->rating?->stars);
    }

    public function test_a_new_piece_with_a_rating_is_refused(): void
    {
        try {
            $this->content(OfferCondition::New, Rating::from(5));
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.rating_not_for_new', $refusal->translationKey);
        }
    }

    public function test_a_rating_without_a_condition_is_refused(): void
    {
        try {
            $this->content(null, Rating::from(3));
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.rating_needs_condition', $refusal->translationKey);
        }
    }

    public function test_a_year_outside_the_plausible_range_is_refused(): void
    {
        try {
            $this->content(null, null, 1800);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.year_is_invalid', $refusal->translationKey);
        }
    }

    public function test_the_price_is_kept(): void
    {
        $content = OfferContent::from(GlassReference::from(null, null), null, null, null, null, null, null, EnteredPrice::fromGrosze(10000));

        $this->assertSame(10000, $content->price?->grosze);
    }
}
