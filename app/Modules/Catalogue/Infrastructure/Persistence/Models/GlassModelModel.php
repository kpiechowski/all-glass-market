<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Read-only model of a Glass model (ADR 0008). Its parts load with it. */
final class GlassModelModel extends ReadOnlyModel
{
    /** @use HasFactory<GlassModelModelFactory> */
    use HasFactory;

    protected $table = 'catalogue_glass_models';

    protected $guarded = [];

    protected static function newFactory(): GlassModelModelFactory
    {
        return GlassModelModelFactory::new();
    }

    protected function casts(): array
    {
        return ['withdrawn' => 'boolean'];
    }

    /** @return HasMany<GlassModelEurocodeModel, $this> */
    public function eurocodes(): HasMany
    {
        return $this->hasMany(GlassModelEurocodeModel::class, 'glass_model_id');
    }

    /**
     * RULE-02: the one label shown on screens.
     *
     * @return HasOne<GlassModelEurocodeModel, $this>
     */
    public function mainEurocode(): HasOne
    {
        return $this->hasOne(GlassModelEurocodeModel::class, 'glass_model_id')->where('is_main', true);
    }

    /** @return HasMany<GlassModelOemCodeModel, $this> */
    public function oemCodes(): HasMany
    {
        return $this->hasMany(GlassModelOemCodeModel::class, 'glass_model_id');
    }

    /** @return BelongsTo<DictionaryValueModel, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(DictionaryValueModel::class, 'position_id');
    }

    /** @return BelongsTo<DictionaryValueModel, $this> */
    public function colour(): BelongsTo
    {
        return $this->belongsTo(DictionaryValueModel::class, 'colour_id');
    }

    /**
     * FR-10: the vehicles the model fits.
     *
     * @return BelongsToMany<VehicleModel, $this>
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(VehicleModel::class, 'catalogue_glass_model_vehicles', 'glass_model_id', 'vehicle_id');
    }

    /** @return BelongsToMany<DictionaryValueModel, $this> */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(DictionaryValueModel::class, 'catalogue_glass_model_equipment', 'glass_model_id', 'dictionary_value_id');
    }
}
