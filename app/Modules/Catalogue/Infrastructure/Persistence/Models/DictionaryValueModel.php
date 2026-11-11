<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Read-only model of one value of any of the six dictionaries. */
final class DictionaryValueModel extends ReadOnlyModel
{
    /** @use HasFactory<DictionaryValueModelFactory> */
    use HasFactory;

    protected $table = 'catalogue_dictionary_values';

    protected $guarded = [];

    protected static function newFactory(): DictionaryValueModelFactory
    {
        return DictionaryValueModelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'dictionary' => Dictionary::class,
            'withdrawn' => 'boolean',
        ];
    }
}
