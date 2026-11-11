<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Catalogue\Domain\Entities\Vehicle;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleDetails;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<Vehicle> */
final class VehicleMapper implements Mapper
{
    public function toEntity(Model $model): Vehicle
    {
        /** @var VehicleModel $model */
        return Vehicle::reconstitute(
            $model->id,
            VehicleName::from($model->name),
            VehicleDetails::from($model->model, $model->year_from, $model->year_to, $model->generation, $model->argic_code),
            $model->make_id,
            $model->body_id,
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'name' => $entity->name()->value,
            'name_key' => $entity->name()->key(),
            'make_id' => $entity->makeId(),
            'model' => $entity->details()->model,
            'body_id' => $entity->bodyId(),
            'year_from' => $entity->details()->yearFrom,
            'year_to' => $entity->details()->yearTo,
            'generation' => $entity->details()->generation,
            'argic_code' => $entity->details()->argicCode,
        ];
    }
}
