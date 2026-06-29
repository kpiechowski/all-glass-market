<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Models;

use App\Modules\Audit\Domain\Contracts\LoggableModel;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'loggable_type', 'loggable_id', 'changes', 'message', 'meta_data'])]
class ChangesLog extends Model
{
    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'meta_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function getLoggableResourceName(): string
    {
        $loggable = $this->loggable;

        if ($loggable instanceof LoggableModel) {
            return $loggable->getLoggableResourceName();
        }

        return class_basename($loggable);
    }

    public function getLoggableTitle(): string
    {
        $loggable = $this->loggable;

        if ($loggable instanceof LoggableModel) {
            return $loggable->getLoggableTitle();
        }

        return (string) $this->loggable_id;
    }

    public function getLoggableUrl(): ?string
    {
        $loggable = $this->loggable;

        if ($loggable instanceof LoggableModel) {
            return $loggable->getLoggableUrl();
        }

        return null;
    }

    public function getLoggableIcon(): ?string
    {
        $loggable = $this->loggable;

        if ($loggable instanceof LoggableModel) {
            return $loggable->getLoggableIcon();
        }

        return null;
    }

    public function matchColorToAction(): string
    {
        return match ($this->meta_data['action'] ?? '') {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default => 'gray',
        };
    }
}
