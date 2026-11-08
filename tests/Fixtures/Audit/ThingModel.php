<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A soft-deleting record type that only the tests register, with the recording observer wired by hand. */
final class ThingModel extends ReadOnlyModel
{
    use SoftDeletes;

    protected $table = 'audit_things';

    protected $guarded = [];

    protected static function booted(): void
    {
        $record = fn (ChangeKind $kind) => fn (self $model) => app(ChangeRecorder::class)->record($model, $kind);

        self::created($record(ChangeKind::Created));
        self::updated($record(ChangeKind::Changed));
        self::deleted($record(ChangeKind::Deleted));
        self::restored($record(ChangeKind::Restored));
    }

    /** @return BelongsToMany<ProducerModel, $this> */
    public function producers(): BelongsToMany
    {
        return $this->belongsToMany(ProducerModel::class, 'audit_thing_producers', 'thing_id', 'producer_id');
    }
}
