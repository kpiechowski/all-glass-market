<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Modules\Audit\Domain\Entities\Change;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use RuntimeException;

final class FailingChangeRepository implements ChangeRepository
{
    public function add(Change $change): int
    {
        throw new RuntimeException('entry could not be stored');
    }
}
