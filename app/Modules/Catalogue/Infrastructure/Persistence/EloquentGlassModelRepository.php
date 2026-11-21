<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\EurocodeHolder;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\GlassModelMapper;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** @extends EloquentRepository<GlassModel> */
final class EloquentGlassModelRepository extends EloquentRepository implements GlassModelRepository
{
    public function __construct(
        private readonly GlassModelMapper $glassModelMapper,
        private readonly ChangeRecorder $recorder,
    ) {
        parent::__construct(GlassModelModel::class, $glassModelMapper);
    }

    public function add(object $entity): int
    {
        $id = parent::add($entity);

        $this->syncParts($id, $entity);

        return $id;
    }

    public function save(object $entity): void
    {
        parent::save($entity);

        if ($this->syncParts($entity->id(), $entity)) {
            ReadOnlyModel::writing(fn (): bool => GlassModelModel::query()->findOrFail($entity->id())->touch());
        }
    }

    public function holderOfEurocode(Eurocode $eurocode, ?int $exceptId = null): ?EurocodeHolder
    {
        $row = GlassModelEurocodeModel::query()
            ->where('code', $eurocode->value)
            ->when($exceptId !== null, fn ($query) => $query->where('glass_model_id', '!=', $exceptId))
            ->first();

        if ($row === null) {
            return null;
        }

        $main = GlassModelEurocodeModel::query()
            ->where('glass_model_id', $row->glass_model_id)
            ->where('is_main', true)
            ->value('code');

        return new EurocodeHolder($row->glass_model_id, (string) $main);
    }

    public function idsUsing(Dictionary $dictionary, int $valueId): array
    {
        $query = GlassModelModel::query();

        match ($dictionary) {
            Dictionary::Position => $query->where('position_id', $valueId),
            Dictionary::Colour => $query->where('colour_id', $valueId),
            Dictionary::Equipment => $query->whereHas('equipment', fn ($equipment) => $equipment->whereKey($valueId)),
            default => $query->whereRaw('1 = 0'),
        };

        return $query->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function idsFittingVehicle(int $vehicleId): array
    {
        return GlassModelModel::query()
            ->whereHas('vehicles', fn ($vehicles) => $vehicles->whereKey($vehicleId))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Eurocodes and OEM codes are child rows, written here and not seen by any observer, so their diff is
     * reported on the Glass model as relation changes; equipment and fitment go through `syncRelation()`.
     *
     * @return bool whether any part changed
     */
    private function syncParts(int $id, GlassModel $entity): bool
    {
        $model = GlassModelModel::query()->findOrFail($id);

        return ReadOnlyModel::writing(function () use ($id, $model, $entity): bool {
            $relationChanges = $this->syncEurocodes($model, $entity) + $this->syncOemCodes($model, $entity);

            if ($relationChanges !== []) {
                $this->recorder->recordRelations($model, $relationChanges);
            }

            $equipmentChanged = $this->pivotChanged($model->equipment(), $entity->equipmentIds());
            $vehiclesChanged = $this->pivotChanged($model->vehicles(), $entity->vehicleIds());

            $this->syncRelation($id, 'equipment', $entity->equipmentIds());
            $this->syncRelation($id, 'vehicles', $entity->vehicleIds());

            return $relationChanges !== [] || $equipmentChanged || $vehiclesChanged;
        });
    }

    /**
     * @param  BelongsToMany<*, GlassModelModel>  $relation
     * @param  list<int>  $wantedIds
     */
    private function pivotChanged(BelongsToMany $relation, array $wantedIds): bool
    {
        $current = $relation->pluck($relation->getRelated()->getQualifiedKeyName())->map(fn ($key): int => (int) $key)->sort()->values()->all();
        sort($wantedIds);

        return $current !== $wantedIds;
    }

    /** @return array<string, array{added: list<string>, removed: list<string>}> relation changes, empty when nothing changed */
    private function syncEurocodes(GlassModelModel $model, GlassModel $entity): array
    {
        $wanted = $this->glassModelMapper->eurocodeRows($entity);
        $oldMain = $model->eurocodes()->where('is_main', true)->value('code');
        $added = [];
        $removed = [];

        foreach ($model->eurocodes()->get() as $row) {
            if (! array_key_exists($row->code, $wanted)) {
                $row->delete();
                $removed[] = (string) $row->getAttribute('code');
            }
        }

        foreach ($wanted as $code => $isMain) {
            $row = $model->eurocodes()->firstOrNew(['code' => (string) $code]);
            $isNew = ! $row->exists;
            $row->is_main = $isMain;

            if ($row->isDirty()) {
                $row->save();
            }

            if ($isNew) {
                $added[] = (string) $code;
            }
        }

        $newMain = $entity->mainEurocode()->value;

        return $this->relationDiff('eurocodes', $added, $removed)
            + ($oldMain !== null && $oldMain !== $newMain ? $this->relationDiff('main_eurocode', [$newMain], [(string) $oldMain]) : []);
    }

    /**
     * A digits-only key ("896028196") is an int key in `$wanted` (PHP casts numeric-string array keys)
     * but a string from the database, so keys are matched by array lookup, never by strict comparison.
     *
     * @return array<string, array{added: list<string>, removed: list<string>}>
     */
    private function syncOemCodes(GlassModelModel $model, GlassModel $entity): array
    {
        $wanted = $this->glassModelMapper->oemCodeRows($entity);
        $added = [];
        $removed = [];

        foreach ($model->oemCodes()->get() as $row) {
            if (! array_key_exists($row->code_key, $wanted) || $wanted[$row->code_key] !== $row->code) {
                $row->delete();
                $removed[] = (string) $row->getAttribute('code');
            }
        }

        $kept = array_flip($model->oemCodes()->pluck('code_key')->all());

        foreach ($wanted as $key => $code) {
            if (! isset($kept[$key])) {
                $model->oemCodes()->create(['code' => $code, 'code_key' => (string) $key]);
                $added[] = (string) $code;
            }
        }

        return $this->relationDiff('oem_codes', $added, $removed);
    }

    /**
     * @param  list<string>  $added
     * @param  list<string>  $removed
     * @return array<string, array{added: list<string>, removed: list<string>}>
     */
    private function relationDiff(string $relation, array $added, array $removed): array
    {
        return $added === [] && $removed === [] ? [] : [$relation => ['added' => $added, 'removed' => $removed]];
    }
}
