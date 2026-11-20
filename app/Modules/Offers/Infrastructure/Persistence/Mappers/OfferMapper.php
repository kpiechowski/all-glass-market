<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use App\Modules\Offers\Domain\ValueObjects\Rating;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<Offer> */
final class OfferMapper implements Mapper
{
    public function toEntity(Model $model): Offer
    {
        /** @var OfferModel $model */
        return Offer::reconstitute(
            $model->id,
            OfferNumber::from($model->number),
            $model->owner_id,
            $model->status,
            OfferContent::from(
                GlassReference::from($model->glass_model_id, $model->identification_hint),
                $model->producer_id,
                $model->year,
                $model->condition,
                $model->rating === null ? null : Rating::from($model->rating),
                $model->description,
                $model->private_notes,
                $model->price_grosze === null ? null : EnteredPrice::fromGrosze($model->price_grosze),
            ),
            OfferPhotos::from($model->public_photos ?? [], $model->private_photos ?? []),
            $model->first_approved_at,
            $model->last_approved_at,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        $content = $entity->content();

        return [
            'number' => $entity->number()->value,
            'owner_id' => $entity->ownerId(),
            'status' => $entity->status(),
            'glass_model_id' => $content->glass->glassModelId,
            'identification_hint' => $content->glass->identificationHint,
            'producer_id' => $content->producerId,
            'year' => $content->year,
            'condition' => $content->condition,
            'rating' => $content->rating?->stars,
            'description' => $content->description,
            'private_notes' => $content->privateNotes,
            'price_grosze' => $content->price?->grosze,
            'public_photos' => $entity->photos()->public,
            'private_photos' => $entity->photos()->private,
            'first_approved_at' => $entity->firstApprovedAt(),
            'last_approved_at' => $entity->lastApprovedAt(),
        ];
    }
}
