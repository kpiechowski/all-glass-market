<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Domain\Actor;
use App\Modules\Audit\Domain\Contracts\RestoresRecord;
use Closure;
use Ecotone\Modelling\CommandBus;

/** The test module's Restore port: sends its own update command, as an edit would. */
final readonly class ThingRestorer implements RestoresRecord
{
    /** @param  Closure(): CommandBus  $bus */
    public function __construct(private Closure $bus) {}

    public function restore(int $recordId, array $values, Actor $actor): void
    {
        ($this->bus)()->send(new ChangeThingCommand($actor, 'update', $recordId, $values));
    }
}
