<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence\Models;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Audit\Infrastructure\Factories\ChangeModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only model of a Change entry. It deliberately has no observer: writing
 * an entry must never produce a RecordChanged of its own, and entries are not
 * audited (they are the audit).
 */
final class ChangeModel extends ReadOnlyModel
{
    /** @use HasFactory<ChangeModelFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'audit_changes';

    protected $guarded = [];

    protected static function newFactory(): ChangeModelFactory
    {
        return ChangeModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'kind' => ChangeKind::class,
            'changes' => 'array',
            'relation_changes' => 'array',
            'occurred_at' => 'immutable_datetime',
            'restored_from_at' => 'immutable_datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actor_id');
    }
}
