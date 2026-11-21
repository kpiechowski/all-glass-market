<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Offers\Domain\Entities\Inquiry;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<Inquiry> */
final class InquiryMapper implements Mapper
{
    public function toEntity(Model $model): Inquiry
    {
        /** @var InquiryModel $model */
        return Inquiry::reconstitute(
            $model->id,
            $model->offer_id,
            $model->sender_id,
            $model->channel,
            $model->note,
            $model->state,
            $model->sent_at,
            $model->read_at,
            $model->answered_at,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'offer_id' => $entity->offerId(),
            'sender_id' => $entity->senderId(),
            'channel' => $entity->channel(),
            'note' => $entity->note(),
            'state' => $entity->state(),
            'sent_at' => $entity->sentAt(),
            'read_at' => $entity->readAt(),
            'answered_at' => $entity->answeredAt(),
        ];
    }
}
