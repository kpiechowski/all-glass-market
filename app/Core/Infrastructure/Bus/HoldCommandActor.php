<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus;

use App\Core\Domain\Actor;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\CommandBus;

/**
 * Makes the command's Actor current for as long as the command is handled.
 * A command without a public Actor `$actor` makes no Actor current.
 */
final class HoldCommandActor
{
    /** Inner to the transaction (higher number runs later), so the frame's changes are
     * flushed before commit. */
    public const PRECEDENCE = 20;

    public function __construct(private readonly ActorStack $actors) {}

    #[Around(precedence: self::PRECEDENCE, pointcut: CommandBus::class)]
    public function hold(MethodInvocation $invocation): mixed
    {
        $message = $invocation->getArguments()[0] ?? null;
        $command = $message instanceof Message ? $message->getPayload() : $message;
        $actor = is_object($command) && ($command->actor ?? null) instanceof Actor ? $command->actor : null;

        return $this->actors->run($actor, fn () => $invocation->proceed());
    }
}
