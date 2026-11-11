<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<DictionaryValue> */
final class DictionaryValueMapper implements Mapper
{
    public function toEntity(Model $model): DictionaryValue
    {
        /** @var DictionaryValueModel $model */
        return DictionaryValue::reconstitute(
            $model->id,
            $model->dictionary,
            DictionaryValueName::from($model->name),
            $model->withdrawn,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'dictionary' => $entity->dictionary(),
            'name' => $entity->name()->value,
            'name_key' => $entity->name()->key(),
            'withdrawn' => $entity->isWithdrawn(),
        ];
    }
}
