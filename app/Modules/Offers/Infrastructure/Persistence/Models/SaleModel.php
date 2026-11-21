<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Read-only model of a Sale (FR-18); `is_open` is 1 while it is not returned, null after, so the database allows one unreturned Sale per Offer; prices in grosze, the VAT rate in basis points, all as at the moment of sale (RULE-09). */
final class SaleModel extends ReadOnlyModel
{
    /** @use HasFactory<SaleModelFactory> */
    use HasFactory;

    /** The type key in Audit's registry, stored in every entry: never rename it. */
    public const string AUDIT_KEY = 'offers.sale';

    protected $table = 'offer_sales';

    protected $guarded = [];

    protected static function newFactory(): SaleModelFactory
    {
        return SaleModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'offer_id' => 'integer',
            'channel' => SaleChannel::class,
            'sale_price_grosze' => 'integer',
            'entered_price_grosze' => 'integer',
            'vat_rate_basis_points' => 'integer',
            'buyers_price_grosze' => 'integer',
            'marked_by_id' => 'integer',
            'sold_at' => 'immutable_datetime',
            'returned_at' => 'immutable_datetime',
            'returned_by_id' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    /** @return BelongsTo<OfferModel, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(OfferModel::class, 'offer_id');
    }

    /** @return BelongsTo<UserModel, $this> */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'marked_by_id');
    }
}
