<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Observers;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;

/** Thin: hands each change of an Inquiry to ChangeRecorder. */
final class InquiryObserver
{
    public function __construct(private readonly ChangeRecorder $recorder) {}

    public function created(InquiryModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Created);
    }

    public function updated(InquiryModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Changed);
    }

    public function deleted(InquiryModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Deleted);
    }
}
