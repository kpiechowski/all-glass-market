<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<Reservation> */
final class ReservationMapper implements Mapper
{
    public function toEntity(Model $model): Reservation
    {
        /** @var ReservationModel $model */
        return Reservation::reconstitute(
            $model->id,
            $model->offer_id,
            $model->author_id,
            $model->made_by_admin,
            $model->note,
            $model->made_at,
            $model->ended_as,
            $model->ended_at,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'offer_id' => $entity->offerId(),
            'author_id' => $entity->authorId(),
            'made_by_admin' => $entity->madeByAdmin(),
            'note' => $entity->note(),
            'made_at' => $entity->madeAt(),
            'ended_as' => $entity->endedAs(),
            'ended_at' => $entity->endedAt(),
            'is_current' => $entity->isCurrent() ? true : null,
        ];
    }
}
