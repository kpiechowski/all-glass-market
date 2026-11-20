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

/** Read-only model of an Offer. The Glass model is only an id; its data comes from Catalogue's module interface (FR-15). */
final class OfferModel extends ReadOnlyModel
{
    /** @use HasFactory<OfferModelFactory> */
    use HasFactory;

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
}
