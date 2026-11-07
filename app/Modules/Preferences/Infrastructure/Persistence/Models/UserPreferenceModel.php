<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Preferences\Infrastructure\Factories\UserPreferenceModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only model of one user's value of one preference. `value` is JSON in the shape its
 * definition gives it.
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property mixed $value
 */
final class UserPreferenceModel extends ReadOnlyModel
{
    /** @use HasFactory<UserPreferenceModelFactory> */
    use HasFactory;

    protected $table = 'user_preferences';

    protected $guarded = [];

    protected static function newFactory(): UserPreferenceModelFactory
    {
        return UserPreferenceModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
