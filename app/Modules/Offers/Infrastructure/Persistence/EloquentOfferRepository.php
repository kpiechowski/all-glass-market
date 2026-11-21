<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Events\OfferStoppedBeingActive;
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
     * FR-23: the one place the fact "this Offer stopped being Active" becomes an event, inside the command's
     * transaction and with its Actor, whichever handler changed the Offer.
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
    }

    public function numberIsTaken(string $number): bool
    {
        return $this->query()->where('number', $number)->exists();
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
