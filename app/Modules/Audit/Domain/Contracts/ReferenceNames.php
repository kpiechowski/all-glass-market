<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Contracts;

/** Looks a referenced record up by its raw id, at display time (D-35, D-40). */
interface ReferenceNames
{
    /**
     * @param  class-string  $modelClass
     * @return array{name: string, removed: bool}|null null when the record is gone entirely
     */
    public function find(string $modelClass, string $nameAttribute, int|string $id): ?array;
}
