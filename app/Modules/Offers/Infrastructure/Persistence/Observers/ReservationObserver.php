<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Observers;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;

/** Thin: hands each change of a Reservation to ChangeRecorder (RULE-10). */
final class ReservationObserver
{
    public function __construct(private readonly ChangeRecorder $recorder) {}

    public function created(ReservationModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Created);
    }

    public function updated(ReservationModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Changed);
    }

    public function deleted(ReservationModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Deleted);
    }
}
