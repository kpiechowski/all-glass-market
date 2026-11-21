<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** FR-16, FR-17, RULE-05. */
class ReservationTest extends TestCase
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-21 10:00:00');
    }

    private function reservation(bool $madeByAdmin = false, string $note = 'Pan Kowalski, tel. 500'): Reservation
    {
        return Reservation::make(4, 11, $madeByAdmin, $note, $this->now());
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

    public function test_a_new_reservation_is_current_and_records_offer_author_note_and_time(): void
    {
        $reservation = $this->reservation(true, '  Pan Kowalski ');

        $this->assertNull($reservation->id());
        $this->assertSame(4, $reservation->offerId());
        $this->assertSame(11, $reservation->authorId());
        $this->assertTrue($reservation->madeByAdmin());
        $this->assertSame('Pan Kowalski', $reservation->note());
        $this->assertEquals($this->now(), $reservation->madeAt());
        $this->assertTrue($reservation->isCurrent());
    }

    public function test_the_note_is_required_and_has_a_limit(): void
    {
        $this->assertSame('offers::exceptions.reservation_note_required', $this->refusalKey(fn () => $this->reservation(note: '   ')));
        $this->assertSame('offers::exceptions.reservation_note_is_too_long', $this->refusalKey(fn () => $this->reservation(note: str_repeat('a', 501))));
    }

    public function test_it_ends_once_as_released_or_sold(): void
    {
        $reservation = $this->reservation();
        $later = $this->now()->addDay();

        $reservation->end(ReservationEnd::Sold, $later);

        $this->assertFalse($reservation->isCurrent());
        $this->assertSame(ReservationEnd::Sold, $reservation->endedAs());
        $this->assertEquals($later, $reservation->endedAt());
        $this->assertSame('offers::exceptions.reservation_already_ended', $this->refusalKey(fn () => $reservation->end(ReservationEnd::Released, $later)));
    }

    public function test_an_admins_reservation_is_locked_for_a_seller(): void
    {
        $this->assertSame('offers::exceptions.reservation_is_locked', $this->refusalKey(fn () => $this->reservation(true)->guardCanBeHandledBy(false)));

        $this->reservation(true)->guardCanBeHandledBy(true);
        $this->reservation(false)->guardCanBeHandledBy(false);
        $this->addToAssertionCount(2);
    }
}
