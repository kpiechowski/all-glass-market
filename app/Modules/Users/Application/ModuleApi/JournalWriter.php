<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\AddJournalEntry\AddJournalEntryCommand;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use Ecotone\Modelling\CommandBus;

/**
 * How any module adds an entry to a User's Journal (FR-29, D-03).
 *
 *     $journal->add(
 *         actor: Actor::user($admin->id),            // or Actor::system('reason')
 *         userId: $offer->ownerId,
 *         kind: 'offer',                             // lowercase word naming the area
 *         severity: JournalSeverity::Success,
 *         title: 'Oferta została aktywowana',
 *         content: "Plain text.\nLine breaks are kept; markup is shown as typed.",
 *         subjectId: $offer->id,                     // optional: the thing the entry is about
 *     );
 *
 * An unknown User gets no entry and the call returns null: the caller's own
 * operation carries on. A Deactivated User still gets entries (D-47). The
 * entry is written in the caller's transaction, and the panel notification is
 * sent after it commits. For `subjectId` to open a page, the module owning the
 * kind registers a resolver once (see JournalSubjects).
 */
final class JournalWriter
{
    public function __construct(private CommandBus $commandBus) {}

    /** @return int|null the entry's id, or null when the User does not exist */
    public function add(
        Actor $actor,
        int $userId,
        string $kind,
        JournalSeverity $severity,
        string $title,
        string $content,
        ?int $subjectId = null,
    ): ?int {
        return $this->commandBus->send(new AddJournalEntryCommand(
            $actor, $userId, $kind, $severity->value, $title, $content, $subjectId,
        ));
    }
}
