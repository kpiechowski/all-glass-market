<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Domain\Contracts\CandidateQuery;
use App\Modules\Catalogue\Domain\Contracts\CandidateSearch;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Illuminate\Database\Eloquent\Builder;

final class GlassModelQueries
{
    public function __construct(private CandidateSearch $search) {}

    /**
     * What a card shows (FR-04): every Eurocode, OEM code, position, colour, equipment and the vehicle count.
     *
     * @return Builder<GlassModelModel>
     */
    public function listing(): Builder
    {
        return GlassModelModel::query()
            ->with(['mainEurocode', 'eurocodes', 'oemCodes', 'position', 'colour', 'equipment'])
            ->withCount('vehicles');
    }

    /**
     * FR-05: the candidate search (D-49) as a list. The models it finds, in its order, each carrying
     * `match_route` and `matched_value`, so a card can say what it matched.
     *
     * @param  Builder<GlassModelModel>|null  $base  a list already narrowed (by the table's filters); the plain listing when null
     * @return Builder<GlassModelModel>
     */
    public function candidates(
        string $text,
        ?int $positionId = null,
        ?int $colourId = null,
        ?int $makeId = null,
        bool $includeWithdrawn = false,
        ?Builder $base = null,
    ): Builder {
        $candidates = $this->search->search(new CandidateQuery($text, $positionId, $colourId, $makeId, $includeWithdrawn));
        $models = ($base ?? $this->listing())->whereKey(array_map(fn ($candidate): int => $candidate->glassModelId, $candidates));

        if ($candidates === []) {
            return $models;
        }

        $routes = $matched = $order = '';
        $routeBindings = $matchedBindings = $orderBindings = [];

        foreach ($candidates as $position => $candidate) {
            $routes .= ' WHEN ? THEN ?';
            $matched .= ' WHEN ? THEN ?';
            $order .= ' WHEN ? THEN ?';
            array_push($routeBindings, $candidate->glassModelId, $candidate->route->value);
            array_push($matchedBindings, $candidate->glassModelId, $candidate->matched);
            array_push($orderBindings, $candidate->glassModelId, $position);
        }

        return $models
            ->selectRaw("CASE catalogue_glass_models.id{$routes} END as match_route", $routeBindings)
            ->selectRaw("CASE catalogue_glass_models.id{$matched} END as matched_value", $matchedBindings)
            ->orderByRaw("CASE catalogue_glass_models.id{$order} END", $orderBindings);
    }

    /**
     * @param  Builder<GlassModelModel>  $query
     * @return Builder<GlassModelModel>
     */
    public function ofPosition(Builder $query, int $positionId): Builder
    {
        return $query->where('position_id', $positionId);
    }

    /**
     * @param  Builder<GlassModelModel>  $query
     * @return Builder<GlassModelModel>
     */
    public function ofColour(Builder $query, int $colourId): Builder
    {
        return $query->where('colour_id', $colourId);
    }

    /**
     * @param  Builder<GlassModelModel>  $query
     * @return Builder<GlassModelModel>
     */
    public function fittingMake(Builder $query, int $makeId): Builder
    {
        return $query->whereHas('vehicles', fn (Builder $vehicles) => $vehicles->where('catalogue_vehicles.make_id', $makeId));
    }

    /**
     * WF-02 move: the models a Eurocode may go to (active or withdrawn), found by a fragment of any of their Eurocodes.
     *
     * @return Builder<GlassModelModel>
     */
    public function moveTargets(string $fragment, int $exceptId): Builder
    {
        $fragment = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($fragment)) ?? '';

        return GlassModelModel::query()
            ->with('mainEurocode')
            ->whereKeyNot($exceptId)
            ->whereHas('eurocodes', fn (Builder $eurocodes) => $eurocodes->where('code', 'like', '%'.$fragment.'%'))
            ->orderBy('id');
    }

    /**
     * @param  Builder<GlassModelModel>  $query
     * @return Builder<GlassModelModel>
     */
    public function onlyWithdrawn(Builder $query): Builder
    {
        return $query->where('withdrawn', true);
    }

    /**
     * @param  Builder<GlassModelModel>  $query
     * @return Builder<GlassModelModel>
     */
    public function onlyActive(Builder $query): Builder
    {
        return $query->where('withdrawn', false);
    }
}
