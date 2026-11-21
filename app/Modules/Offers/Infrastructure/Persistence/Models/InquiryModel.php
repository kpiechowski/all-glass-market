<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Read-only model of an Inquiry (FR-21). */
final class InquiryModel extends ReadOnlyModel
{
    /** @use HasFactory<InquiryModelFactory> */
    use HasFactory;

    /** The type key in Audit's registry, stored in every entry: never rename it. */
    public const string AUDIT_KEY = 'offers.inquiry';

    protected $table = 'offer_inquiries';

    protected $guarded = [];

    protected static function newFactory(): InquiryModelFactory
    {
        return InquiryModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'offer_id' => 'integer',
            'sender_id' => 'integer',
            'channel' => SaleChannel::class,
            'state' => InquiryState::class,
            'sent_at' => 'immutable_datetime',
            'read_at' => 'immutable_datetime',
            'answered_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<OfferModel, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(OfferModel::class, 'offer_id');
    }

    /** @return BelongsTo<UserModel, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'sender_id');
    }
}
