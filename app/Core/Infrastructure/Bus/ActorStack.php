<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus;

use App\Core\Contracts\CommandContext;
use App\Core\Domain\Actor;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Exceptions\NoCurrentActorException;

/**
 * Holds one frame per command being handled: its Actor and the changes it
 * recorded. A nested command pushes its own frame and the outer one is current
 * again once it finishes.
 *
 * Recorded changes are buffered in the current frame. When the frame closes
 * successfully they are coalesced to one RecordChanged per record and
 * dispatched synchronously, still inside the command's transaction, with that
 * frame's Actor. When the command throws, its buffer is discarded.
 *
 * It is also the CommandContext: values set around a callback are copied into
 * every frame pushed inside it and stamped on that frame's events when it flushes.
 */
final class ActorStack implements CommandContext
{
    /** @var list<array{actor: Actor|null, events: list<RecordChanged>, context: array<string, mixed>}> */
    private array $frames = [];

    /** @var array<string, mixed> */
    private array $context = [];

    public function __construct(private readonly RecordChangeCoalescer $coalescer = new RecordChangeCoalescer) {}

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(?Actor $actor, callable $callback): mixed
    {
        $this->frames[] = ['actor' => $actor, 'events' => [], 'context' => $this->context];

        try {
            $result = $callback();
            $this->flush();

            return $result;
        } finally {
            array_pop($this->frames);
        }
    }

    public function with(string $key, mixed $value, callable $callback): mixed
    {
        $previous = $this->context;
        $this->context[$key] = $value;

        try {
            return $callback();
        } finally {
            $this->context = $previous;
        }
    }

    public function hasCurrent(): bool
    {
        return $this->frames !== [] && $this->frames[array_key_last($this->frames)]['actor'] !== null;
    }

    public function current(): Actor
    {
        return ($this->frames === [] ? null : $this->frames[array_key_last($this->frames)]['actor'])
            ?? throw NoCurrentActorException::outsideCommand();
    }

    public function buffer(RecordChanged $event): void
    {
        $this->current();

        $this->frames[array_key_last($this->frames)]['events'][] = $event;
    }

    private function flush(): void
    {
        $top = array_key_last($this->frames);

        while ($this->frames[$top]['events'] !== []) {
            $batch = $this->frames[$top]['events'];
            $this->frames[$top]['events'] = [];

            foreach ($this->coalescer->coalesce($batch) as $event) {
                event($event->withContext($this->frames[$top]['context']));
            }
        }
    }
}
