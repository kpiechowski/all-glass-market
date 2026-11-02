<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Factories\JournalEntryModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only model of a Journal entry. `user_id` is the owner, `subject_id`
 * the optional subject the kind refers to.
 */
final class JournalEntryModel extends ReadOnlyModel
{
    /** @use HasFactory<JournalEntryModelFactory> */
    use HasFactory;

    protected $table = 'journal_entries';

    protected $guarded = [];

    protected static function newFactory(): JournalEntryModelFactory
    {
        return JournalEntryModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'severity' => JournalSeverity::class,
            'is_read' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
