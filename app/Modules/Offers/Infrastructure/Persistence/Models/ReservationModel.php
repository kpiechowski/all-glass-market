<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Read-only model of a Reservation (FR-16). `is_current` is 1 while it is current, null after it ended. */
final class ReservationModel extends ReadOnlyModel
{
    /** @use HasFactory<ReservationModelFactory> */
    use HasFactory;

    /** The type key in Audit's registry, stored in every entry: never rename it. */
    public const string AUDIT_KEY = 'offers.reservation';

    protected $table = 'offer_reservations';

    protected $guarded = [];

    protected static function newFactory(): ReservationModelFactory
    {
        return ReservationModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'offer_id' => 'integer',
            'author_id' => 'integer',
            'made_by_admin' => 'boolean',
            'made_at' => 'immutable_datetime',
            'ended_as' => ReservationEnd::class,
            'ended_at' => 'immutable_datetime',
            'is_current' => 'boolean',
        ];
    }

    /** @return BelongsTo<OfferModel, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(OfferModel::class, 'offer_id');
    }

    /** @return BelongsTo<UserModel, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'author_id');
    }
}
