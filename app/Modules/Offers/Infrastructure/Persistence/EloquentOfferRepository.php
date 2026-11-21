<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OfferAnnouncement;
use App\Modules\Offers\Domain\Events\OfferBecameCandidate;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Domain\Events\OfferReserved;
use App\Modules\Offers\Domain\Events\OfferSold;
use App\Modules\Offers\Domain\Events\OfferStoppedBeingActive;
use App\Modules\Offers\Domain\Events\OfferStoppedBeingCandidate;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\OfferMapper;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use Illuminate\Database\Eloquent\Builder;

/** @extends EloquentRepository<Offer> */
final class EloquentOfferRepository extends EloquentRepository implements OfferRepository
{
    public function __construct(OfferMapper $mapper, private readonly ActorStack $actors)
    {
        parent::__construct(OfferModel::class, $mapper);
    }

    /**
     * FR-23, §10: the one place the facts about an Offer become events (stopped being Active; the announcements
     * for channels), inside the command's transaction, whichever handler changed the Offer.
     *
     * @param  Offer  $entity
     */
    public function save(object $entity): void
    {
        parent::save($entity);

        if ($entity->pullLeftActive()) {
            OfferStoppedBeingActive::dispatch(
                (int) $entity->id(),
                $this->actors->hasCurrent() ? $this->actors->current() : Actor::system('offer-stopped-being-active'),
            );
        }

        foreach ($entity->pullAnnouncements() as $announcement) {
            $offerId = (int) $entity->id();

            match ($announcement) {
                OfferAnnouncement::BecameCandidate => OfferBecameCandidate::dispatch($offerId),
                OfferAnnouncement::ChangedWhileCandidate => OfferChangedWhileCandidate::dispatch($offerId),
                OfferAnnouncement::Reserved => OfferReserved::dispatch($offerId),
                OfferAnnouncement::Sold => OfferSold::dispatch($offerId),
                OfferAnnouncement::StoppedBeingCandidate => OfferStoppedBeingCandidate::dispatch($offerId),
            };
        }
    }

    public function numberIsTaken(string $number): bool
    {
        return $this->query()->where('number', $number)->exists();
    }

    public function idsOfOwner(int $ownerId): array
    {
        return $this->query()->where('owner_id', $ownerId)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function isCandidate(int $offerId): bool
    {
        return $this->candidates()->whereKey($offerId)->exists();
    }

    public function candidateIdsOfGlassModel(int $glassModelId): array
    {
        return $this->candidates()->where('glass_model_id', $glassModelId)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function candidateIdsOfProducer(int $producerId): array
    {
        return $this->candidates()->where('producer_id', $producerId)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    /** @return Builder<OfferModel> */
    private function candidates(): Builder
    {
        return $this->query()->whereIn('status', OfferDomainPolicy::CANDIDATE_STATUSES)->whereNull('trashed_at');
    }

    public function pricedOffersOfOwner(int $ownerId): array
    {
        return array_map(
            fn (array $row): array => ['id' => $row['id'], 'grosze' => $row['grosze']],
            $this->priced($this->query()->where('owner_id', $ownerId)),
        );
    }

    public function pricedOffers(): array
    {
        return $this->priced($this->query());
    }

    /**
     * @param  Builder<OfferModel>  $query
     * @return list<array{id: int, ownerId: int, grosze: int}>
     */
    private function priced(Builder $query): array
    {
        return $query->whereNotNull('price_grosze')->orderBy('id')->get(['id', 'owner_id', 'price_grosze'])
            ->map(fn (OfferModel $offer): array => ['id' => $offer->id, 'ownerId' => $offer->owner_id, 'grosze' => $offer->price_grosze])
            ->all();
    }
}
