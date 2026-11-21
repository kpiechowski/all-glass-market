<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Entities\Inquiry;
use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** FR-21, FR-22, FR-23, §7 Inquiry states. */
class InquiryTest extends TestCase
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-21 10:00:00');
    }

    private function inquiry(string $note = 'Pan Kowalski, tel. 500'): Inquiry
    {
        return Inquiry::send(4, 11, SaleChannel::Olx, $note, $this->now(), true);
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

    public function test_a_new_inquiry_is_sent_and_records_offer_sender_channel_note_and_time(): void
    {
        $inquiry = $this->inquiry('  Pan Kowalski ');

        $this->assertNull($inquiry->id());
        $this->assertSame(4, $inquiry->offerId());
        $this->assertSame(11, $inquiry->senderId());
        $this->assertSame(SaleChannel::Olx, $inquiry->channel());
        $this->assertSame('Pan Kowalski', $inquiry->note());
        $this->assertSame(InquiryState::Sent, $inquiry->state());
        $this->assertTrue($inquiry->sentAt()->equalTo($this->now()));
        $this->assertTrue($inquiry->isOpen());
    }

    public function test_the_channel_is_optional(): void
    {
        $this->assertNull(Inquiry::send(4, 11, null, 'Notatka', $this->now(), true)->channel());
    }

    public function test_a_blank_or_too_long_note_is_refused(): void
    {
        $this->assertSame('offers::exceptions.inquiry_note_required', $this->refusalKey(fn () => $this->inquiry('   ')));
        $this->assertSame('offers::exceptions.inquiry_note_is_too_long', $this->refusalKey(fn () => $this->inquiry(str_repeat('a', 501))));
        $this->assertSame(500, mb_strlen($this->inquiry(str_repeat('a', 500))->note()));
    }

    public function test_opening_marks_it_read_once(): void
    {
        $inquiry = $this->inquiry();

        $inquiry->markRead($this->now());
        $inquiry->markRead($this->now()->addDay());

        $this->assertSame(InquiryState::Read, $inquiry->state());
        $this->assertTrue($inquiry->readAt()->equalTo($this->now()));
    }

    public function test_accepting_or_rejecting_an_open_inquiry_closes_it_with_the_time_of_the_answer(): void
    {
        foreach ([[InquiryState::Accepted, fn (Inquiry $i) => $i->accept($this->now())], [InquiryState::Rejected, fn (Inquiry $i) => $i->reject($this->now())]] as [$state, $answer]) {
            foreach ([false, true] as $read) {
                $inquiry = $this->inquiry();
                if ($read) {
                    $inquiry->markRead($this->now());
                }

                $answer($inquiry);

                $this->assertSame($state, $inquiry->state());
                $this->assertTrue($inquiry->answeredAt()->equalTo($this->now()));
                $this->assertFalse($inquiry->isOpen());
            }
        }
    }

    public function test_sent_or_read_becomes_outdated(): void
    {
        $sent = $this->inquiry();
        $read = $this->inquiry();
        $read->markRead($this->now());

        $sent->outdate();
        $read->outdate();

        $this->assertSame(InquiryState::Outdated, $sent->state());
        $this->assertSame(InquiryState::Outdated, $read->state());
    }

    public function test_a_closed_inquiry_cannot_be_answered_or_outdated_again_and_stays_closed_when_opened(): void
    {
        foreach ([InquiryState::Accepted, InquiryState::Rejected, InquiryState::Outdated] as $state) {
            $inquiry = Inquiry::reconstitute(1, 4, 11, null, 'Notatka', $state, $this->now(), null, null);

            $this->assertSame('offers::exceptions.inquiry_is_closed', $this->refusalKey(fn () => $inquiry->accept($this->now())));
            $this->assertSame('offers::exceptions.inquiry_is_closed', $this->refusalKey(fn () => $inquiry->reject($this->now())));
            $this->assertSame('offers::exceptions.inquiry_is_closed', $this->refusalKey(fn () => $inquiry->outdate()));

            $inquiry->markRead($this->now());
            $this->assertSame($state, $inquiry->state());
        }
    }

    public function test_a_reservation_can_name_the_inquiry_it_came_from(): void
    {
        $from = Reservation::make(4, 11, true, 'Pan Kowalski', $this->now(), 9);
        $plain = Reservation::make(4, 11, false, 'Pan Kowalski', $this->now());

        $this->assertSame(9, $from->inquiryId());
        $this->assertNull($plain->inquiryId());
    }

    public function test_marking_read_twice_keeps_the_first_read_time(): void
    {
        $inquiry = $this->inquiry();
        $inquiry->markRead($this->now());
        $inquiry->markRead($this->now()->addHour());

        $this->assertTrue($inquiry->readAt()->equalTo($this->now()));
    }

    public function test_an_inquiry_about_an_offer_that_cannot_receive_one_is_refused(): void
    {
        $key = $this->refusalKey(fn () => Inquiry::send(4, 11, null, 'Notatka', $this->now(), false));

        $this->assertSame('offers::exceptions.offer_cannot_receive_inquiry', $key);
    }
}
