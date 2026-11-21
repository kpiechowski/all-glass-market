<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\Rating;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** FR-04: 1 to 5 stars. */
class RatingTest extends TestCase
{
    public function test_one_to_five_is_accepted(): void
    {
        foreach ([1, 2, 3, 4, 5] as $stars) {
            $this->assertSame($stars, Rating::from($stars)->stars);
        }
    }

    /** @return array<string, array{int}> */
    public static function outOfRange(): array
    {
        return ['zero' => [0], 'six' => [6], 'negative' => [-1]];
    }

    #[DataProvider('outOfRange')]
    public function test_anything_else_is_refused(int $stars): void
    {
        try {
            Rating::from($stars);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.rating_is_invalid', $refusal->translationKey);
        }
    }
}
