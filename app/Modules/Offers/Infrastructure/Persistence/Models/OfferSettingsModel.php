<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Infrastructure\Factories\OfferSettingsModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Read-only model of Offers' settings: the single row holding the VAT rate (FR-30). */
final class OfferSettingsModel extends ReadOnlyModel
{
    /** @use HasFactory<OfferSettingsModelFactory> */
    use HasFactory;

    protected $table = 'offer_settings';

    protected $guarded = [];

    protected static function newFactory(): OfferSettingsModelFactory
    {
        return OfferSettingsModelFactory::new();
    }

    protected function casts(): array
    {
        return ['vat_rate_basis_points' => 'integer'];
    }
}
