<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Domain\Entities\Sale;
use App\Modules\Offers\Domain\Repositories\SaleRepository;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\SaleMapper;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;

/** @extends EloquentRepository<Sale> */
final class EloquentSaleRepository extends EloquentRepository implements SaleRepository
{
    private SaleMapper $saleMapper;

    public function __construct(SaleMapper $mapper)
    {
        parent::__construct(SaleModel::class, $mapper);

        $this->saleMapper = $mapper;
    }

    public function findCurrentForOffer(int $offerId): ?Sale
    {
        $model = $this->query()->where('offer_id', $offerId)->where('is_open', true)->first();

        return $model === null ? null : $this->saleMapper->toEntity($model);
    }

    public function removeAllOfOffer(int $offerId): void
    {
        foreach ($this->query()->where('offer_id', $offerId)->get() as $model) {
            ReadOnlyModel::writing(fn () => $model->delete());
        }
    }
}
