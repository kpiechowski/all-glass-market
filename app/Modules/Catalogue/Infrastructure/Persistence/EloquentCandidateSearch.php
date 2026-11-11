<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\Candidate;
use App\Modules\Catalogue\Domain\Contracts\CandidateQuery;
use App\Modules\Catalogue\Domain\Contracts\CandidateSearch;
use App\Modules\Catalogue\Domain\Enums\MatchRoute;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-22, D-49. The routes run one after another in result order; each skips the models an earlier
 * route already found (one result per model, under its best route) and asks only for as many as
 * the limit still allows. Within a route, models come in id order.
 */
final class EloquentCandidateSearch implements CandidateSearch
{
    public function search(CandidateQuery $query): array
    {
        $found = [];

        foreach (MatchRoute::cases() as $route) {
            $remaining = $query->limit === null ? null : $query->limit - count($found);

            if ($remaining !== null && $remaining <= 0) {
                break;
            }

            foreach ($this->hits($route, $query, array_keys($found), $remaining) as $candidate) {
                $found[$candidate->glassModelId] = $candidate;
            }
        }

        return array_values($found);
    }

    public function matchingIds(CandidateQuery $query): array
    {
        if (! $query->isSearchable() && $query->hasFilter()) {
            return $this->candidateModels($query)->orderBy('catalogue_glass_models.id')->pluck('catalogue_glass_models.id')->map(fn ($id): int => (int) $id)->all();
        }

        return array_map(fn (Candidate $candidate): int => $candidate->glassModelId, $this->search($query->withoutLimit()));
    }

    /**
     * @param  list<int>  $alreadyFound
     * @return list<Candidate>
     */
    private function hits(MatchRoute $route, CandidateQuery $query, array $alreadyFound, ?int $limit): array
    {
        [$relation, $match] = match ($route) {
            MatchRoute::Eurocode => ['eurocodes', $this->eurocodeMatch($query)],
            MatchRoute::Oem => ['oemCodes', $this->oemMatch($query)],
            MatchRoute::Vehicle => ['vehicles', $this->vehicleMatch($query)],
        };

        if ($match === null) {
            return [];
        }

        return $this->candidateModels($query)
            ->whereNotIn('catalogue_glass_models.id', $alreadyFound)
            ->whereHas($relation, $match)
            ->with([$relation => $match])
            ->orderBy('catalogue_glass_models.id')
            ->when($limit !== null, fn (Builder $models) => $models->limit($limit))
            ->get()
            ->map(fn (GlassModelModel $model): Candidate => new Candidate(
                $model->id,
                $route,
                $this->matchedValue($route, $model->getRelation($relation)->first()),
            ))
            ->all();
    }

    private function matchedValue(MatchRoute $route, Model $hit): string
    {
        return match ($route) {
            MatchRoute::Vehicle => (string) $hit->getAttribute('name'),
            default => (string) $hit->getAttribute('code'),
        };
    }

    /** The filters and the withdrawn rule, shared by every route. @return Builder<GlassModelModel> */
    private function candidateModels(CandidateQuery $query): Builder
    {
        return GlassModelModel::query()
            ->when(! $query->includeWithdrawn, fn (Builder $models) => $models->where('catalogue_glass_models.withdrawn', false))
            ->when($query->positionId !== null, fn (Builder $models) => $models->where('catalogue_glass_models.position_id', $query->positionId))
            ->when($query->colourId !== null, fn (Builder $models) => $models->where('catalogue_glass_models.colour_id', $query->colourId))
            ->when($query->makeId !== null, fn (Builder $models) => $models->whereHas('vehicles', fn (Builder $vehicles) => $vehicles->where('catalogue_vehicles.make_id', $query->makeId)));
    }

    /** The match closures serve both `whereHas` (a Builder) and `with` (a relation), so they take neither type. */
    private function eurocodeMatch(CandidateQuery $query): ?\Closure
    {
        $fragment = $query->eurocodeFragment();

        return $fragment === null ? null : fn ($codes) => $codes->where('code', 'like', '%'.$fragment.'%');
    }

    private function oemMatch(CandidateQuery $query): ?\Closure
    {
        $fragment = $query->oemFragment();

        return $fragment === null ? null : fn ($codes) => $codes->where('code_key', 'like', '%'.$fragment.'%');
    }

    /** Every typed word must match the name, model, generation, ARGIC code or make of one vehicle. */
    private function vehicleMatch(CandidateQuery $query): ?\Closure
    {
        $words = $query->vehicleWords();

        if ($words === []) {
            return null;
        }

        return function ($vehicles) use ($words): void {
            foreach ($words as $word) {
                $like = '%'.$word.'%';

                $vehicles->where(fn (Builder $any) => $any
                    ->where('catalogue_vehicles.name', 'like', $like)
                    ->orWhere('catalogue_vehicles.model', 'like', $like)
                    ->orWhere('catalogue_vehicles.generation', 'like', $like)
                    ->orWhere('catalogue_vehicles.argic_code', 'like', $like)
                    ->orWhereHas('make', fn (Builder $make) => $make->where('name', 'like', $like)));
            }
        };
    }
}
