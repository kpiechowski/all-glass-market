<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Observers;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;

/** Thin: hands each change to ChangeRecorder and nothing else. */
final class GlassModelObserver
{
    public function __construct(private readonly ChangeRecorder $recorder) {}

    public function created(GlassModelModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Created);
    }

    public function updated(GlassModelModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Changed);
    }

    public function deleted(GlassModelModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Deleted);
    }
}
