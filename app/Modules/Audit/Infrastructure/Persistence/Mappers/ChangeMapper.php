<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Audit\Domain\Entities\Change;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<Change> */
final class ChangeMapper implements Mapper
{
    public function toEntity(Model $model): Change
    {
        /** @var ChangeModel $model */
        return Change::reconstitute(
            $model->id,
            $model->record_type,
            $model->record_id,
            $model->kind,
            $model->title,
            $model->changes ?? [],
            $model->relation_changes ?? [],
            $model->actor_id,
            $model->actor_reason,
            DateTimeImmutable::createFromInterface($model->occurred_at),
            $model->restored_from_id,
            $model->restored_from_at === null ? null : DateTimeImmutable::createFromInterface($model->restored_from_at),
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'record_type' => $entity->recordType(),
            'record_id' => $entity->recordId(),
            'kind' => $entity->kind(),
            'title' => $entity->title(),
            'changes' => $entity->changes(),
            'relation_changes' => $entity->relationChanges(),
            'actor_id' => $entity->actorId(),
            'actor_reason' => $entity->actorReason(),
            'occurred_at' => $entity->occurredAt(),
            'restored_from_id' => $entity->restoredFromId(),
            'restored_from_at' => $entity->restoredFromAt(),
        ];
    }
}
