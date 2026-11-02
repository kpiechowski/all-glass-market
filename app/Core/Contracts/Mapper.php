<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TEntity of object
 */
interface Mapper
{
    /**
     * @return TEntity
     */
    public function toEntity(Model $model): object;

    /**
     * @param  TEntity  $entity
     * @return array<string, mixed>
     */
    public function toRow(object $entity): array;
}
