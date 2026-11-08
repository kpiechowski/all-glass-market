<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence;

use App\Core\Exceptions\ReadOnlyModelException;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for every module model. Filament reads it, only repository adapters
 * and factories write it, inside writing().
 */
abstract class ReadOnlyModel extends Model
{
    private static int $writeScopes = 0;

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function writing(callable $callback): mixed
    {
        self::$writeScopes++;

        try {
            return $callback();
        } finally {
            self::$writeScopes--;
        }
    }

    public function save(array $options = []): bool
    {
        self::assertWritable(static::class, 'save');

        return parent::save($options);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        self::assertWritable(static::class, 'update');

        return parent::update($attributes, $options);
    }

    public function delete(): ?bool
    {
        self::assertWritable(static::class, 'delete');

        return parent::delete();
    }

    public function push(): bool
    {
        self::assertWritable(static::class, 'push');

        return parent::push();
    }

    /**
     * Untyped on purpose: the SoftDeletes trait declares forceDelete() without
     * a return type, and a typed parent here would make every soft-deleting
     * model a fatal error. The trait still ends in delete(), which is guarded.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        self::assertWritable(static::class, 'forceDelete');

        return parent::forceDelete();
    }

    public static function assertWritable(string $modelClass, string $operation): void
    {
        if (self::$writeScopes === 0) {
            throw ReadOnlyModelException::forWrite($modelClass, $operation);
        }
    }

    public function newEloquentBuilder($query): ReadOnlyBuilder
    {
        return new ReadOnlyBuilder($query);
    }
}
