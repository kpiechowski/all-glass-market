<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A widget with soft deletes, a tags relation and the recording observer wired by hand. */
final class RecordedWidgetModel extends ReadOnlyModel
{
    use SoftDeletes;

    protected $table = 'widgets';

    protected $guarded = [];

    protected static function booted(): void
    {
        $record = fn (ChangeKind $kind) => fn (self $model) => app(ChangeRecorder::class)->record($model, $kind);

        self::created($record(ChangeKind::Created));
        self::updated($record(ChangeKind::Changed));
        self::deleted($record(ChangeKind::Deleted));
        self::restored($record(ChangeKind::Restored));
    }

    /** @return BelongsToMany<self, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'widget_tags', 'widget_id', 'tag_id');
    }
}
