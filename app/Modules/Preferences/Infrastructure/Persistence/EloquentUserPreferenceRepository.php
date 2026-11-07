<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Preferences\Domain\Entities\UserPreference;
use App\Modules\Preferences\Domain\Repositories\UserPreferenceRepository;
use App\Modules\Preferences\Infrastructure\Persistence\Mappers\UserPreferenceMapper;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;

/** @extends EloquentRepository<UserPreference> */
final class EloquentUserPreferenceRepository extends EloquentRepository implements UserPreferenceRepository
{
    public function __construct(private UserPreferenceMapper $mapper)
    {
        parent::__construct(UserPreferenceModel::class, $mapper);
    }

    public function findFor(int $userId, string $key): ?UserPreference
    {
        $model = $this->query()->where('user_id', $userId)->where('key', $key)->first();

        return $model === null ? null : $this->mapper->toEntity($model);
    }
}
