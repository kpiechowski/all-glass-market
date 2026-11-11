<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Read-only model of the drawing of one dictionary value (FR-15-FR-17). */
final class ThumbnailMappingModel extends ReadOnlyModel
{
    /** @use HasFactory<ThumbnailMappingModelFactory> */
    use HasFactory;

    protected $table = 'catalogue_thumbnail_mappings';

    protected $guarded = [];

    protected static function newFactory(): ThumbnailMappingModelFactory
    {
        return ThumbnailMappingModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'dictionary' => Dictionary::class,
            'shape' => GlassShape::class,
            'mirrored' => 'boolean',
            'opacity' => 'integer',
            'pictogram' => Pictogram::class,
        ];
    }
}
