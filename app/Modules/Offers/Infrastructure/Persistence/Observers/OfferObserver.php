<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Observers;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/**
 * Thin: hands each change to ChangeRecorder and nothing else. The recorder
 * buffers per command and emits one RecordChanged per record when the command
 * finishes (kind, field changes, relation changes, Actor).
 * Relation changes are not seen here; the repository adapter reports them
 * through EloquentRepository::syncRelation().
 * Emission is synchronous, before the command's transaction commits.
 */
final class OfferObserver
{
    public function __construct(private readonly ChangeRecorder $recorder) {}

    public function created(OfferModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Created);
    }

    public function updated(OfferModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Changed);
    }

    public function deleted(OfferModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Deleted);
    }
}
