<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Services;

use App\Core\Domain\Actor;
use App\Modules\Offers\Domain\Entities\Inquiry;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Users\Application\ModuleApi\JournalWriter;
use App\Modules\Users\Domain\Enums\JournalSeverity;

/**
 * FR-34: what the admin who sent an Inquiry reads in their Journal when it is accepted, rejected or becomes
 * Outdated. Nobody is told about their own action.
 */
final class InquirySenderJournal
{
    public function __construct(private readonly JournalWriter $journal) {}

    public function accepted(Actor $actor, Inquiry $inquiry, Offer $offer): void
    {
        $this->write($actor, $inquiry, $offer, JournalSeverity::Success, 'inquiry_accepted');
    }

    public function rejected(Actor $actor, Inquiry $inquiry, Offer $offer): void
    {
        $this->write($actor, $inquiry, $offer, JournalSeverity::Rejection, 'inquiry_rejected');
    }

    public function outdated(Actor $actor, Inquiry $inquiry, Offer $offer): void
    {
        $this->write($actor, $inquiry, $offer, JournalSeverity::Info, 'inquiry_outdated');
    }

    private function write(Actor $actor, Inquiry $inquiry, Offer $offer, JournalSeverity $severity, string $event): void
    {
        if ($inquiry->senderId() === $actor->id) {
            return;
        }

        $parameters = ['number' => $offer->number()->value];

        $this->journal->add(
            $actor,
            $inquiry->senderId(),
            OwnerJournal::KIND,
            $severity,
            __("offers::journal.{$event}.title", $parameters),
            __("offers::journal.{$event}.content", $parameters),
            $offer->id(),
        );
    }
}
