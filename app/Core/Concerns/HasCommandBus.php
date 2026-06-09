<?php

declare(strict_types=1);

namespace App\Core\Concerns;

use Ecotone\Modelling\CommandBus;

trait HasCommandBus
{
    protected CommandBus $commandBus;

    public function bootHasCommandBus(): void
    {
        $this->commandBus = app(CommandBus::class);
    }
}
