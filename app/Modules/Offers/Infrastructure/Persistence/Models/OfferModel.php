<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Read-only model of an Offer. The Glass model is only an id; its data comes from Catalogue's module interface (FR-15). */
final class OfferModel extends ReadOnlyModel
{
    /** @use HasFactory<OfferModelFactory> */
    use HasFactory;

    /** The Offer's type key in Audit's registry, stored in every entry: never rename it. */
    public const string AUDIT_KEY = 'offers.offer';

    protected $table = 'offers';

    protected $guarded = [];

    protected static function newFactory(): OfferModelFactory
    {
        return OfferModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'owner_id' => 'integer',
            'status' => OfferStatus::class,
            'glass_model_id' => 'integer',
            'producer_id' => 'integer',
            'year' => 'integer',
            'condition' => OfferCondition::class,
            'rating' => 'integer',
            'price_grosze' => 'integer',
            'public_photos' => 'array',
            'private_photos' => 'array',
            'first_approved_at' => 'immutable_datetime',
            'last_approved_at' => 'immutable_datetime',
            'changed_since_approval' => 'boolean',
            'trashed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<UserModel, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'owner_id');
    }

    /** @return BelongsTo<DictionaryValueModel, $this> */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(DictionaryValueModel::class, 'producer_id');
    }

    /** FR-16, RULE-05: the one current Reservation, if any. @return HasOne<ReservationModel, $this> */
    public function currentReservation(): HasOne
    {
        return $this->hasOne(ReservationModel::class, 'offer_id')->where('is_current', true);
    }

    /** FR-20: the Sales of the Offer, newest first, returned ones included. @return HasMany<SaleModel, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(SaleModel::class, 'offer_id')->latest('id');
    }
}
