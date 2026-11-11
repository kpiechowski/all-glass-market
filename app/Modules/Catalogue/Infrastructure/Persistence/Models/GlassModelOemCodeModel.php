<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** One OEM code of a Glass model: `code` as stored, `code_key` the form codes are compared by (RULE-04). */
final class GlassModelOemCodeModel extends ReadOnlyModel
{
    /** @use HasFactory<GlassModelOemCodeModelFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'catalogue_glass_model_oem_codes';

    protected $guarded = [];

    protected static function newFactory(): GlassModelOemCodeModelFactory
    {
        return GlassModelOemCodeModelFactory::new();
    }
}
