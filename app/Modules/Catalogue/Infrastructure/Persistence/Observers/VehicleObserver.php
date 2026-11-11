<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Observers;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;

/** Thin: hands each change to ChangeRecorder and nothing else. */
final class VehicleObserver
{
    public function __construct(private readonly ChangeRecorder $recorder) {}

    public function created(VehicleModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Created);
    }

    public function updated(VehicleModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Changed);
    }

    public function deleted(VehicleModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Deleted);
    }
}
