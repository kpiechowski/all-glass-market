<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Domain\Actor;

/** One scenario of writes to a Thing, sent on a bus by the tests. */
final readonly class ChangeThingCommand
{
    /**
     * @param  'create'|'update'|'delete'|'restore'|'sync'  $action
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $producerIds
     */
    public function __construct(
        public Actor $actor,
        public string $action,
        public ?int $thingId = null,
        public array $attributes = [],
        public array $producerIds = [],
    ) {}
}
