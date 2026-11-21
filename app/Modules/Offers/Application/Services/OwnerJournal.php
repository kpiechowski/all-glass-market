<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Services;

use App\Core\Domain\Actor;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Users\Application\ModuleApi\JournalWriter;
use App\Modules\Users\Domain\Enums\JournalSeverity;

/**
 * FR-34: what an Offer's owner reads in their Journal when someone else acts on the Offer. Entries
 * go through Users' module interface only, in the handler's transaction; nobody is told about
 * their own action. Shared by the handlers of this module.
 */
final class OwnerJournal
{
    /** The Journal kind Offers registers a subject resolver for. */
    public const string KIND = 'offer';

    public function __construct(private readonly JournalWriter $journal) {}

    public function approved(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Success, 'approved');
    }

    public function rejected(Actor $actor, Offer $offer, string $reason): void
    {
        $this->write($actor, $offer, JournalSeverity::Rejection, 'rejected', ['reason' => $reason]);
    }

    public function changedByAdmin(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'changed_by_admin');
    }

    public function glassModelChanged(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'glass_model_changed');
    }

    public function withdrawn(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'withdrawn');
    }

    public function deleted(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'deleted');
    }

    public function reserved(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'reserved');
    }

    public function released(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'released');
    }

    public function sold(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Success, 'sold');
    }

    public function inquirySent(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'inquiry_sent');
    }

    public function returned(Actor $actor, Offer $offer): void
    {
        $this->write($actor, $offer, JournalSeverity::Info, 'returned');
    }

    /** @param array<string, string> $parameters */
    private function write(Actor $actor, Offer $offer, JournalSeverity $severity, string $event, array $parameters = []): void
    {
        if ($offer->ownerId() === $actor->id) {
            return;
        }

        $parameters += ['number' => $offer->number()->value];

        $this->journal->add(
            $actor,
            $offer->ownerId(),
            self::KIND,
            $severity,
            __("offers::journal.{$event}.title", $parameters),
            __("offers::journal.{$event}.content", $parameters),
            $offer->id(),
        );
    }
}
