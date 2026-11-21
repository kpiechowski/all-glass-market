<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class OfferQueries
{
    /**
     * RULE-11: a seller gets their own Offers only, never one in the Trash (FR-25); whoever manages all
     * Offers gets every one, the Trash included, so a deleted Offer can still be opened and restored.
     * The lists narrow an admin's query with `outsideTrash` and `onlyTrash`.
     *
     * @return Builder<OfferModel>
     */
    public function visibleTo(int $userId, bool $managesAll): Builder
    {
        return OfferModel::query()
            ->with(['owner', 'producer', 'currentReservation'])
            ->when(! $managesAll, fn (Builder $query) => $this->outsideTrash($query)->where('owner_id', $userId));
    }

    /** One Offer by id for a read outside the lists (the private photo route); the caller checks who may see it. */
    public function find(int $id): ?OfferModel
    {
        return OfferModel::query()->find($id);
    }

    /**
     * FR-11: the approval queue, the Pending tab and its counter. Admins only; the caller decides who may see it.
     *
     * @return Builder<OfferModel>
     */
    public function pendingApproval(): Builder
    {
        return $this->onlyPending(OfferModel::query()->with(['owner', 'producer']));
    }

    /**
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function onlyPending(Builder $query): Builder
    {
        return $this->outsideTrash($query)->where('status', OfferStatus::Pending);
    }

    /**
     * FR-25: every list but the Trash tab leaves out deleted Offers.
     *
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function outsideTrash(Builder $query): Builder
    {
        return $query->whereNull('trashed_at');
    }

    /**
     * Catalogue's "N ofert": how many Offers of the query refer to each of the Glass models.
     *
     * @param  Builder<OfferModel>  $query
     * @param  list<int>  $glassModelIds
     * @return array<int, int> by Glass model id; a model without Offers is left out
     */
    public function countByGlassModel(Builder $query, array $glassModelIds): array
    {
        return $query
            ->whereIn('offers.glass_model_id', $glassModelIds)
            ->selectRaw('offers.glass_model_id as glass_model_id, count(*) as offers_count')
            ->groupBy('offers.glass_model_id')
            ->reorder()
            ->toBase()
            ->pluck('offers_count', 'glass_model_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * FR-25: the admin's Trash tab.
     *
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function onlyTrash(Builder $query): Builder
    {
        return $query->whereNotNull('trashed_at');
    }

    /**
     * FR-31: one status tab; like every tab but the Trash it leaves out deleted Offers.
     *
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function onlyStatus(Builder $query, OfferStatus $status): Builder
    {
        return $this->outsideTrash($query)->where('status', $status);
    }

    /**
     * FR-32: how many of the owner's Offers are in each status, the Trash left out.
     *
     * @return array<string, int> by status value; a status with no Offers is absent
     */
    public function countsByStatusOf(int $ownerId): array
    {
        return $this->outsideTrash(OfferModel::query())
            ->where('owner_id', $ownerId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /** FR-32: the owner's Sales made in the month of `$now` (by `sold_at`), returned ones excluded. */
    public function soldInMonthOf(int $ownerId, CarbonInterface $now): int
    {
        return SaleModel::query()
            ->whereNull('returned_at')
            ->whereBetween('sold_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->whereHas('offer', fn (Builder $offers) => $offers->where('owner_id', $ownerId))
            ->count();
    }

    /**
     * FR-36: the last twelve months up to and including the month of `$now`, oldest first, each with the Offers
     * first approved that month ("added") and the Sales made that month ("sold", returned ones left out). An
     * Offer in the Trash counts in neither line, and neither does its Sale. `$ownerId` narrows both to one seller.
     * Two grouped reads, whatever the window; a month with nothing is filled with zeros.
     *
     * @return list<array{month: string, added: int, sold: int}> `month` is `Y-m`
     */
    public function monthlyActivity(CarbonInterface $now, ?int $ownerId = null): array
    {
        $first = $now->copy()->startOfMonth()->subMonths(11);
        $window = [$first, $now->copy()->endOfMonth()];

        $added = $this->outsideTrash(OfferModel::query())
            ->when($ownerId !== null, fn (Builder $offers) => $offers->where('owner_id', $ownerId))
            ->whereBetween('first_approved_at', $window)
            ->selectRaw($this->monthSql('first_approved_at').' as month, count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $sold = SaleModel::query()
            ->whereNull('returned_at')
            ->whereBetween('sold_at', $window)
            ->whereHas('offer', fn (Builder $offers) => $this->outsideTrash($offers)
                ->when($ownerId !== null, fn (Builder $query) => $query->where('owner_id', $ownerId)))
            ->selectRaw($this->monthSql('sold_at').' as month, count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $months = [];

        for ($offset = 0; $offset < 12; $offset++) {
            $key = $first->copy()->addMonths($offset)->format('Y-m');
            $months[] = ['month' => $key, 'added' => (int) ($added[$key] ?? 0), 'sold' => (int) ($sold[$key] ?? 0)];
        }

        return $months;
    }

    /**
     * Sellers that own at least one Offer, by name: the options of the admin's chart filter.
     *
     * @return array<int, string> by user id
     */
    public function sellersWithOffers(): array
    {
        return UserModel::query()
            ->whereIn('id', OfferModel::query()->select('owner_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** `Y-m` of a datetime column on MySQL and SQLite alike. */
    private function monthSql(string $column): string
    {
        return OfferModel::query()->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * FR-28, RULE-08 in SQL: the buyer's price in grosze (the entered price for a Company owner, the entered
     * price plus VAT rounded half up for a Private one), the same arithmetic as `BuyersPricePolicy`. The
     * remainder form keeps the division exact on every database; the rate is an integer, so it is inlined.
     */
    public function buyersPriceSql(int $vatBasisPoints): string
    {
        $grossTimesTwo = '(offers.price_grosze * '.(10000 + $vatBasisPoints).' * 2 + 10000)';

        return 'CASE WHEN (SELECT users.account_type FROM users WHERE users.id = offers.owner_id) = \''.OwnerAccountType::Company->value.'\''
            .' THEN offers.price_grosze'
            .' ELSE ('.$grossTimesTwo.' - ('.$grossTimesTwo.' % 20000)) / 20000 END';
    }

    /**
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function orderedByBuyersPrice(Builder $query, string $direction, int $vatBasisPoints): Builder
    {
        return $query->orderByRaw($this->buyersPriceSql($vatBasisPoints).' '.($direction === 'desc' ? 'desc' : 'asc'));
    }

    /**
     * FR-31: Offers whose buyer's price lies in the range (grosze, either end optional). An Offer without a price never matches.
     *
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function withBuyersPriceBetween(Builder $query, ?int $fromGrosze, ?int $toGrosze, int $vatBasisPoints): Builder
    {
        $price = $this->buyersPriceSql($vatBasisPoints);

        return $query
            ->whereNotNull('offers.price_grosze')
            ->when($fromGrosze !== null, fn (Builder $offers) => $offers->whereRaw("({$price}) >= ?", [$fromGrosze]))
            ->when($toGrosze !== null, fn (Builder $offers) => $offers->whereRaw("({$price}) <= ?", [$toGrosze]));
    }

    /**
     * FR-31: Offers of the given Glass models (ids from Catalogue's search, or one id from the "N ofert" link).
     *
     * @param  Builder<OfferModel>  $query
     * @param  list<int>  $glassModelIds
     * @return Builder<OfferModel>
     */
    public function ofGlassModels(Builder $query, array $glassModelIds): Builder
    {
        return $query->whereIn('offers.glass_model_id', $glassModelIds);
    }

    /**
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function withoutGlassModel(Builder $query): Builder
    {
        return $query->whereNull('offers.glass_model_id');
    }

    /**
     * FR-31, D-46: Offers shown with this many stars; a new piece is shown with five and stores no rating.
     *
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function withDisplayedStars(Builder $query, int $stars): Builder
    {
        return $query->where(fn (Builder $offers) => $offers
            ->where('offers.rating', $stars)
            ->when($stars === 5, fn (Builder $offers) => $offers->orWhere('offers.condition', OfferCondition::New->value)));
    }
}
