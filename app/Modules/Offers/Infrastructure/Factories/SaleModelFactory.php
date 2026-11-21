<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends ModelFactory<SaleModel> */
final class SaleModelFactory extends ModelFactory
{
    protected $model = SaleModel::class;

    /** A Sale made on the panel at 150 zł entered, 23% VAT, 184,50 zł for the buyer, sold for the buyer's price. */
    public function definition(): array
    {
        return [
            'offer_id' => OfferModelFactory::new(),
            'channel' => SaleChannel::Panel,
            'sale_price_grosze' => 18450,
            'entered_price_grosze' => 15000,
            'vat_rate_basis_points' => 2300,
            'buyers_price_grosze' => 18450,
            'marked_by_id' => UserModel::factory(),
            'sold_at' => now(),
            'returned_at' => null,
            'return_reason' => null,
            'returned_by_id' => null,
        ];
    }

    public function forOffer(int $offerId): static
    {
        return $this->state(fn (): array => ['offer_id' => $offerId]);
    }

    public function returned(int $byId, string $reason = 'Klient zrezygnował'): static
    {
        return $this->state(fn (): array => ['returned_at' => now(), 'return_reason' => $reason, 'returned_by_id' => $byId]);
    }
}
