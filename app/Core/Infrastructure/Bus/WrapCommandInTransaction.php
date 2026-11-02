<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Modelling\CommandBus;
use Illuminate\Support\Facades\DB;

/**
 * Runs every command sent on the command bus in one database transaction.
 * Handlers never open their own.
 */
final class WrapCommandInTransaction
{
    #[Around(pointcut: CommandBus::class)]
    public function transactional(MethodInvocation $invocation): mixed
    {
        return DB::transaction(fn () => $invocation->proceed());
    }
}
