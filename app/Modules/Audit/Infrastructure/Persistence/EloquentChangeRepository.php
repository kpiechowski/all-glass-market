<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Audit\Domain\Entities\Change;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use App\Modules\Audit\Infrastructure\Persistence\Mappers\ChangeMapper;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;

/**
 * Adds entries only. The inherited save() and remove() are never reached,
 * because the port does not expose them (RULE-04).
 *
 * @extends EloquentRepository<Change>
 */
final class EloquentChangeRepository extends EloquentRepository implements ChangeRepository
{
    public function __construct(ChangeMapper $mapper)
    {
        parent::__construct(ChangeModel::class, $mapper);
    }
}
