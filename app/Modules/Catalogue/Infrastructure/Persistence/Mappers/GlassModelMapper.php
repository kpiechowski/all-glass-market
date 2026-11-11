<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelOemCodeModel;
use Illuminate\Database\Eloquent\Model;

/**
 * `toRow` holds the model's own columns; the parts saved with it (Eurocodes, OEM codes, equipment)
 * are written by the adapter from `eurocodeRows()`, `oemCodeRows()` and the entity's equipment ids.
 *
 * @implements Mapper<GlassModel>
 */
final class GlassModelMapper implements Mapper
{
    public function toEntity(Model $model): GlassModel
    {
        /** @var GlassModelModel $model */
        $model->loadMissing(['eurocodes', 'oemCodes', 'equipment']);

        $eurocodes = $model->eurocodes->sortBy('id')->values();

        return GlassModel::reconstitute(
            $model->id,
            $eurocodes->map(fn (GlassModelEurocodeModel $row): Eurocode => Eurocode::from($row->code))->all(),
            Eurocode::from($eurocodes->firstWhere('is_main', true)->code),
            $model->position_id,
            $model->colour_id,
            $model->equipment->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            $model->oemCodes->sortBy('id')->map(fn (GlassModelOemCodeModel $row): OemCode => OemCode::tryFrom($row->code))->values()->all(),
            $model->withdrawn,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'position_id' => $entity->positionId(),
            'colour_id' => $entity->colourId(),
            'withdrawn' => $entity->isWithdrawn(),
        ];
    }

    /** @return array<string, bool> Eurocode => is it the main one */
    public function eurocodeRows(GlassModel $entity): array
    {
        $rows = [];

        foreach ($entity->eurocodes() as $eurocode) {
            $rows[$eurocode->value] = $eurocode->equals($entity->mainEurocode());
        }

        return $rows;
    }

    /** @return array<string, string> compared form => code as stored */
    public function oemCodeRows(GlassModel $entity): array
    {
        $rows = [];

        foreach ($entity->oemCodes() as $code) {
            $rows[$code->key()] = $code->value;
        }

        return $rows;
    }
}
