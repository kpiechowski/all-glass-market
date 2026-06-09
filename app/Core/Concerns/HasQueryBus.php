<?php

declare(strict_types=1);

namespace App\Core\Concerns;

use Ecotone\Modelling\QueryBus;

trait HasQueryBus
{
    protected QueryBus $queryBus;

    public function bootHasQueryBus(): void
    {
        $this->queryBus = app(QueryBus::class);
    }
}
