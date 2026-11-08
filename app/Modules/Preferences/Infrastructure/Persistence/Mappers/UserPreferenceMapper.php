<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Preferences\Domain\Entities\UserPreference;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<UserPreference> */
final class UserPreferenceMapper implements Mapper
{
    public function toEntity(Model $model): UserPreference
    {
        /** @var UserPreferenceModel $model */
        return UserPreference::reconstitute(
            $model->id,
            $model->user_id,
            $model->key,
            $model->value,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'user_id' => $entity->userId(),
            'key' => $entity->key(),
            'value' => $entity->value(),
        ];
    }
}
