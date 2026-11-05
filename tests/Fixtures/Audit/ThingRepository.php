<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Contracts\Mapper;
use Illuminate\Database\Eloquent\Model;

/** @extends EloquentRepository<object> */
final class ThingRepository extends EloquentRepository
{
    public function __construct()
    {
        parent::__construct(ThingModel::class, new class implements Mapper
        {
            public function toEntity(Model $model): object
            {
                return (object) $model->getAttributes();
            }

            public function toRow(object $entity): array
            {
                return (array) $entity;
            }
        });
    }

    /** @param list<int> $producerIds */
    public function syncProducers(int $id, array $producerIds): void
    {
        $this->syncRelation($id, 'producers', $producerIds);
    }
}
