<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Read-only model of a vehicle. */
final class VehicleModel extends ReadOnlyModel
{
    /** @use HasFactory<VehicleModelFactory> */
    use HasFactory;

    protected $table = 'catalogue_vehicles';

    protected $guarded = [];

    protected static function newFactory(): VehicleModelFactory
    {
        return VehicleModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'make_id' => 'integer',
            'body_id' => 'integer',
            'year_from' => 'integer',
            'year_to' => 'integer',
        ];
    }

    /** @return BelongsTo<DictionaryValueModel, $this> */
    public function make(): BelongsTo
    {
        return $this->belongsTo(DictionaryValueModel::class, 'make_id');
    }

    /**
     * FR-09: the Glass models the vehicle fits.
     *
     * @return BelongsToMany<GlassModelModel, $this>
     */
    public function glassModels(): BelongsToMany
    {
        return $this->belongsToMany(GlassModelModel::class, 'catalogue_glass_model_vehicles', 'vehicle_id', 'glass_model_id');
    }

    /** @return BelongsTo<DictionaryValueModel, $this> */
    public function body(): BelongsTo
    {
        return $this->belongsTo(DictionaryValueModel::class, 'body_id');
    }
}
