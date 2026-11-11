<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\EurocodeHolder;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\GlassModelMapper;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;

/** @extends EloquentRepository<GlassModel> */
final class EloquentGlassModelRepository extends EloquentRepository implements GlassModelRepository
{
    public function __construct(private readonly GlassModelMapper $glassModelMapper)
    {
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

    /** @return bool whether any part changed */
    private function syncParts(int $id, GlassModel $entity): bool
    {
        $model = GlassModelModel::query()->findOrFail($id);

        return ReadOnlyModel::writing(function () use ($model, $entity): bool {
            $changed = $this->syncEurocodes($model, $entity);
            $changed = $this->syncOemCodes($model, $entity) || $changed;

            $equipment = $model->equipment()->sync($entity->equipmentIds());

            $vehicles = $model->vehicles()->sync($entity->vehicleIds());

            return $changed
                || $vehicles['attached'] !== [] || $vehicles['detached'] !== []
                || $equipment['attached'] !== [] || $equipment['detached'] !== [];
        });
    }

    private function syncEurocodes(GlassModelModel $model, GlassModel $entity): bool
    {
        $wanted = $this->glassModelMapper->eurocodeRows($entity);
        $changed = false;

        foreach ($model->eurocodes()->get() as $row) {
            if (! array_key_exists($row->code, $wanted)) {
                $row->delete();
                $changed = true;
            }
        }

        foreach ($wanted as $code => $isMain) {
            $row = $model->eurocodes()->firstOrNew(['code' => $code]);
            $row->is_main = $isMain;

            if ($row->isDirty()) {
                $row->save();
                $changed = true;
            }
        }

        return $changed;
    }

    private function syncOemCodes(GlassModelModel $model, GlassModel $entity): bool
    {
        $wanted = $this->glassModelMapper->oemCodeRows($entity);
        $changed = false;

        foreach ($model->oemCodes()->get() as $row) {
            if (! array_key_exists($row->code_key, $wanted) || $wanted[$row->code_key] !== $row->code) {
                $row->delete();
                $changed = true;
            }
        }

        $kept = $model->oemCodes()->pluck('code_key')->all();

        foreach ($wanted as $key => $code) {
            if (! in_array($key, $kept, true)) {
                $model->oemCodes()->create(['code' => $code, 'code_key' => $key]);
                $changed = true;
            }
        }

        return $changed;
    }
}
