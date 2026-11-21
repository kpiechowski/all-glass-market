<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferSettingsModel;

final class EloquentVatRateSetting implements VatRateSetting
{
    public function current(): VatRate
    {
        $basisPoints = OfferSettingsModel::query()->value('vat_rate_basis_points');

        return $basisPoints === null ? VatRate::initial() : VatRate::fromBasisPoints((int) $basisPoints);
    }

    public function change(VatRate $rate): void
    {
        ReadOnlyModel::writing(function () use ($rate): void {
            $row = OfferSettingsModel::query()->first() ?? new OfferSettingsModel;
            $row->fill(['vat_rate_basis_points' => $rate->basisPoints])->save();
        });
    }
}
