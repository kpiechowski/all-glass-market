<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Modules\Offers\Domain\Enums\OfferStatus;

/** FR-31, FR-32: the tabs of the Offers list and the Offer status each one holds. "all" and "trash" hold no single status. */
final class OfferListTabs
{
    public const string ALL = 'all';

    public const string TRASH = 'trash';

    /** @var array<string, OfferStatus> tab key by status, in tab order */
    private const array BY_STATUS = [
        'pending' => OfferStatus::Pending,
        'active' => OfferStatus::Active,
        'reserved' => OfferStatus::Reserved,
        'sold' => OfferStatus::Sold,
        'withdrawn' => OfferStatus::Withdrawn,
        'rejected' => OfferStatus::Rejected,
        'drafts' => OfferStatus::Draft,
    ];

    /** @return array<string, OfferStatus> */
    public static function statusTabs(): array
    {
        return self::BY_STATUS;
    }

    public static function statusOf(?string $tab): ?OfferStatus
    {
        return self::BY_STATUS[$tab ?? self::ALL] ?? null;
    }

    public static function isTrash(?string $tab): bool
    {
        return $tab === self::TRASH;
    }
}
