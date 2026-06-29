<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Models;

use App\Modules\Audit\Domain\Enums\JournalTypeEnum;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['offer_id', 'user_id', 'type', 'title', 'content', 'is_read'])]
class UserJournal extends Model
{
    protected function casts(): array
    {
        return [
            'type' => JournalTypeEnum::class,
            'is_read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
