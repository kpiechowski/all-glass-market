<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\OfferMapper;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/** @extends EloquentRepository<Offer> */
final class EloquentOfferRepository extends EloquentRepository implements OfferRepository
{
    public function __construct(OfferMapper $mapper)
    {
        parent::__construct(OfferModel::class, $mapper);
    }

    public function numberIsTaken(string $number): bool
    {
        return $this->query()->where('number', $number)->exists();
    }
}
