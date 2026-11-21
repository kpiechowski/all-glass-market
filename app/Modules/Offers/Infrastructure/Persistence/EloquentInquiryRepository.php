<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Domain\Entities\Inquiry;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\InquiryMapper;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;

/** @extends EloquentRepository<Inquiry> */
final class EloquentInquiryRepository extends EloquentRepository implements InquiryRepository
{
    private InquiryMapper $inquiryMapper;

    public function __construct(InquiryMapper $mapper)
    {
        parent::__construct(InquiryModel::class, $mapper);

        $this->inquiryMapper = $mapper;
    }

    public function openOfOffer(int $offerId): array
    {
        return $this->query()
            ->where('offer_id', $offerId)
            ->whereIn('state', [InquiryState::Sent, InquiryState::Read])
            ->orderBy('id')
            ->get()
            ->map(fn (InquiryModel $model): Inquiry => $this->inquiryMapper->toEntity($model))
            ->all();
    }

    public function removeAllOfOffer(int $offerId): void
    {
        foreach ($this->query()->where('offer_id', $offerId)->get() as $model) {
            ReadOnlyModel::writing(fn () => $model->delete());
        }
    }
}
