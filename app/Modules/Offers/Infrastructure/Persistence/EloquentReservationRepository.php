<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\ReservationMapper;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;

/** @extends EloquentRepository<Reservation> */
final class EloquentReservationRepository extends EloquentRepository implements ReservationRepository
{
    private ReservationMapper $reservationMapper;

    public function __construct(ReservationMapper $mapper)
    {
        parent::__construct(ReservationModel::class, $mapper);

        $this->reservationMapper = $mapper;
    }

    public function findCurrentForOffer(int $offerId): ?Reservation
    {
        $model = $this->query()->where('offer_id', $offerId)->where('is_current', true)->first();

        return $model === null ? null : $this->reservationMapper->toEntity($model);
    }

    public function removeAllOfOffer(int $offerId): void
    {
        foreach ($this->query()->where('offer_id', $offerId)->get() as $model) {
            ReadOnlyModel::writing(fn () => $model->delete());
        }
    }
}
