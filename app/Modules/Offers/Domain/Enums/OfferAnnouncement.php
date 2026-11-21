<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

/** §10: the facts about an Offer that channels are told, as the entity records them for one save. */
enum OfferAnnouncement
{
    case BecameCandidate;
    case ChangedWhileCandidate;
    case Reserved;
    case Sold;
    case StoppedBeingCandidate;
}
