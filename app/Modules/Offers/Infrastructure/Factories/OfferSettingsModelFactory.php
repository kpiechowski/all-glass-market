<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferSettingsModel;

/** @extends ModelFactory<OfferSettingsModel> */
final class OfferSettingsModelFactory extends ModelFactory
{
    protected $model = OfferSettingsModel::class;

    public function definition(): array
    {
        return ['vat_rate_basis_points' => 2300];
    }
}
