<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;
use App\Modules\Catalogue\Domain\ValueObjects\FillColour;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ThumbnailMappingModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<ThumbnailMapping> */
final class ThumbnailMappingMapper implements Mapper
{
    public function toEntity(Model $model): ThumbnailMapping
    {
        /** @var ThumbnailMappingModel $model */
        return ThumbnailMapping::reconstitute(
            $model->id,
            $model->dictionary_value_id,
            $model->dictionary,
            $model->shape,
            $model->mirrored,
            $model->fill === null ? null : FillColour::from($model->fill),
            $model->opacity,
            $model->pictogram,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'dictionary_value_id' => $entity->dictionaryValueId(),
            'dictionary' => $entity->dictionary(),
            'shape' => $entity->shape(),
            'mirrored' => $entity->isMirrored(),
            'fill' => $entity->fill()?->value,
            'opacity' => $entity->opacity(),
            'pictogram' => $entity->pictogram(),
        ];
    }
}
