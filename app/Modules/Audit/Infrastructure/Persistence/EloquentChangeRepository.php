<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Audit\Domain\Entities\Change;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use App\Modules\Audit\Infrastructure\Persistence\Mappers\ChangeMapper;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use DateTimeImmutable;

/**
 * Adds entries only; the one removal is retention's removeOlderThan(). The
 * inherited save() and remove() are never reached, because the port does not
 * expose them (RULE-04).
 *
 * @extends EloquentRepository<Change>
 */
final class EloquentChangeRepository extends EloquentRepository implements ChangeRepository
{
    public function __construct(ChangeMapper $mapper)
    {
        parent::__construct(ChangeModel::class, $mapper);
    }

    public function find(int $id): ?Change
    {
        return parent::find($id);
    }

    public function removeOlderThan(DateTimeImmutable $cutoff): int
    {
        return ReadOnlyModel::writing(fn (): int => $this->query()->where('occurred_at', '<', $cutoff)->delete());
    }
}
