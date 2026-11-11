<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** One Eurocode of a Glass model; the code is unique across the catalogue (RULE-01). */
final class GlassModelEurocodeModel extends ReadOnlyModel
{
    /** @use HasFactory<GlassModelEurocodeModelFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'catalogue_glass_model_eurocodes';

    protected $guarded = [];

    protected static function newFactory(): GlassModelEurocodeModelFactory
    {
        return GlassModelEurocodeModelFactory::new();
    }

    protected function casts(): array
    {
        return ['is_main' => 'boolean'];
    }
}
