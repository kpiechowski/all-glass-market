<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\CommandBus;

/** Records which Actor is current before, inside and after a nested command. */
final class ActedHandler
{
    /** @var list<Actor> */
    public array $seen = [];

    public function __construct(private readonly ActorStack $actors) {}

    #[CommandHandler]
    public function handle(ActedCommand $command, CommandBus $bus): void
    {
        $this->seen[] = $this->actors->current();

        if ($command->nested) {
            $bus->send(new InnerActedCommand(Actor::system('inner job')));
            $this->seen[] = $this->actors->current();
        }
    }

    #[CommandHandler]
    public function handleInner(InnerActedCommand $command): void
    {
        $this->seen[] = $this->actors->current();
    }
}
