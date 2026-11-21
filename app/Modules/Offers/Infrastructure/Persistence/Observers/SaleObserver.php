<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Observers;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;

/** Thin: hands each change of a Sale to ChangeRecorder (RULE-10). */
final class SaleObserver
{
    public function __construct(private readonly ChangeRecorder $recorder) {}

    public function created(SaleModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Created);
    }

    public function updated(SaleModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Changed);
    }

    public function deleted(SaleModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Deleted);
    }
}
