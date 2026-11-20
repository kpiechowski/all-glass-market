<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use PHPUnit\Framework\TestCase;

/** RULE-02, FR-02: a Glass model or an identification hint, never both. */
class GlassReferenceTest extends TestCase
{
    public function test_a_glass_model_is_referred_to_by_id(): void
    {
        $reference = GlassReference::from(7, null);

        $this->assertSame(7, $reference->glassModelId);
        $this->assertNull($reference->identificationHint);
    }

    public function test_a_hint_is_trimmed(): void
    {
        $this->assertSame('VIN WVW123', GlassReference::from(null, '  VIN WVW123 ')->identificationHint);
    }

    public function test_neither_is_allowed_in_a_draft(): void
    {
        $reference = GlassReference::from(null, '   ');

        $this->assertNull($reference->glassModelId);
        $this->assertNull($reference->identificationHint);
    }

    public function test_a_glass_model_and_a_hint_together_are_refused(): void
    {
        try {
            GlassReference::from(7, 'OEM 123');
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.glass_model_and_hint_together', $refusal->translationKey);
        }
    }

    public function test_a_blank_hint_next_to_a_glass_model_is_no_hint(): void
    {
        $this->assertSame(7, GlassReference::from(7, '  ')->glassModelId);
    }

    public function test_a_hint_over_the_limit_is_refused(): void
    {
        $this->expectException(OfferRefused::class);

        GlassReference::from(null, str_repeat('a', 256));
    }
}
