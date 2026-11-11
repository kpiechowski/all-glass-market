<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\DictionaryValueMapper;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;

/** @extends EloquentRepository<DictionaryValue> */
final class EloquentDictionaryValueRepository extends EloquentRepository implements DictionaryValueRepository
{
    public function __construct(DictionaryValueMapper $mapper)
    {
        parent::__construct(DictionaryValueModel::class, $mapper);
    }

    public function nameIsTaken(Dictionary $dictionary, string $name, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('dictionary', $dictionary)
            ->where('name_key', DictionaryValueName::from($name)->key())
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
