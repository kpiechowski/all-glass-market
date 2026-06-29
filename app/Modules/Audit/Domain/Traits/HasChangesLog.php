<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Traits;

use App\Modules\Audit\Application\Services\ChangesLogService;
use App\Modules\Audit\Domain\Models\ChangesLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasChangesLog
{
    public static function bootHasChangesLog(): void
    {
        static::created(function ($model) {
            app(ChangesLogService::class)->createCreatedEntry($model);
        });

        static::updated(function ($model) {
            app(ChangesLogService::class)->createUpdatedEntry($model);
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                $model->changesLogs()->delete();

                return;
            }

            app(ChangesLogService::class)->createDeletedEntry($model);
        });
    }

    public function getLoggableAttributes(): array
    {
        return static::$loggable ?? ['*'];
    }

    public function getNotLoggableAttributes(): array
    {
        return static::$notLoggable ?? ['id', 'created_at', 'updated_at', 'deleted_at'];
    }

    public function changesLogs(): MorphMany
    {
        return $this->morphMany(ChangesLog::class, 'loggable');
    }
}
