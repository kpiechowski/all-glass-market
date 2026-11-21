<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** RULE-13, D-29: "AG" and eight digits. */
class OfferNumberTest extends TestCase
{
    public function test_ag_and_eight_digits_is_accepted(): void
    {
        $this->assertSame('AG00012345', OfferNumber::from('AG00012345')->value);
    }

    public function test_a_number_is_built_from_a_sequence_value_padded_to_eight_digits(): void
    {
        $this->assertSame('AG00000042', OfferNumber::fromDigits(42)->value);
    }

    /** @return array<string, array{string}> */
    public static function malformed(): array
    {
        return [
            'too short' => ['AG1234567'],
            'too long' => ['AG123456789'],
            'lowercase' => ['ag12345678'],
            'other prefix' => ['XX12345678'],
            'letters inside' => ['AG1234567a'],
        ];
    }

    #[DataProvider('malformed')]
    public function test_a_malformed_number_is_refused(string $number): void
    {
        try {
            OfferNumber::from($number);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.number_is_invalid', $refusal->translationKey);
        }
    }
}
