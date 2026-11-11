<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;
use App\Modules\Catalogue\Domain\Repositories\ThumbnailMappingRepository;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\ThumbnailMappingMapper;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ThumbnailMappingModel;

/** @extends EloquentRepository<ThumbnailMapping> */
final class EloquentThumbnailMappingRepository extends EloquentRepository implements ThumbnailMappingRepository
{
    public function __construct(private readonly ThumbnailMappingMapper $thumbnailMapper)
    {
        parent::__construct(ThumbnailMappingModel::class, $thumbnailMapper);
    }

    public function findForValue(int $dictionaryValueId): ?ThumbnailMapping
    {
        $model = $this->query()->where('dictionary_value_id', $dictionaryValueId)->first();

        return $model === null ? null : $this->thumbnailMapper->toEntity($model);
    }

    public function all(): array
    {
        return $this->query()->get()
            ->map(fn (ThumbnailMappingModel $model): ThumbnailMapping => $this->thumbnailMapper->toEntity($model))
            ->all();
    }
}
