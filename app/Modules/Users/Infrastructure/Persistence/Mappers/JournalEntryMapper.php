<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Users\Domain\Entities\JournalEntry;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<JournalEntry> */
final class JournalEntryMapper implements Mapper
{
    public function toEntity(Model $model): JournalEntry
    {
        /** @var JournalEntryModel $model */
        return JournalEntry::reconstitute(
            $model->id,
            $model->user_id,
            $model->kind,
            $model->severity,
            $model->title,
            $model->content,
            $model->subject_id,
            $model->is_read,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'user_id' => $entity->ownerId(),
            'kind' => $entity->kind(),
            'severity' => $entity->severity(),
            'title' => $entity->title(),
            'content' => $entity->content(),
            'subject_id' => $entity->subjectId(),
            'is_read' => $entity->isRead(),
        ];
    }
}
