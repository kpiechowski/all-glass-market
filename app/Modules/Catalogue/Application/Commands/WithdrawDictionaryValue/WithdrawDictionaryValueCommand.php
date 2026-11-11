<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\WithdrawDictionaryValue;

use App\Core\Domain\Actor;

final readonly class WithdrawDictionaryValueCommand
{
    public function __construct(
        public Actor $actor,
        public int $valueId,
    ) {}
}
