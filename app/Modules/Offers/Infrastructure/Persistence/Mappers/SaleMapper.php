<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Offers\Domain\Entities\Sale;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

/** @implements Mapper<Sale> */
final class SaleMapper implements Mapper
{
    public function toEntity(Model $model): Sale
    {
        /** @var SaleModel $model */
        try {
            return Sale::reconstitute(
                $model->id,
                $model->offer_id,
                $model->channel,
                $model->sale_price_grosze,
                EnteredPrice::fromGrosze($model->entered_price_grosze),
                VatRate::fromBasisPoints($model->vat_rate_basis_points),
                $model->buyers_price_grosze,
                $model->marked_by_id,
                $model->sold_at,
                $model->returned_at,
                $model->return_reason,
                $model->returned_by_id,
            );
        } catch (OfferRefused $refusal) {
            throw new UnexpectedValueException("Sale #{$model->id} cannot be read: {$refusal->translationKey}.", 0, $refusal);
        }
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'offer_id' => $entity->offerId(),
            'channel' => $entity->channel(),
            'sale_price_grosze' => $entity->salePriceGrosze(),
            'entered_price_grosze' => $entity->enteredPrice()->grosze,
            'vat_rate_basis_points' => $entity->vatRate()->basisPoints,
            'buyers_price_grosze' => $entity->buyersPriceGrosze(),
            'marked_by_id' => $entity->markedById(),
            'sold_at' => $entity->soldAt(),
            'returned_at' => $entity->returnedAt(),
            'return_reason' => $entity->returnReason(),
            'returned_by_id' => $entity->returnedById(),
            'is_open' => $entity->returnedAt() === null ? true : null,
        ];
    }
}
