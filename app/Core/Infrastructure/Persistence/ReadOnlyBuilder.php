<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent builder of a ReadOnlyModel: every write method needs the write scope.
 *
 * @template TModel of ReadOnlyModel
 *
 * @extends Builder<TModel>
 */
final class ReadOnlyBuilder extends Builder
{
    public function update(array $values)
    {
        $this->assertWritable('update');

        return parent::update($values);
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        $this->assertWritable('upsert');

        return parent::upsert($values, $uniqueBy, $update);
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        $this->assertWritable('increment');

        return parent::increment($column, $amount, $extra);
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        $this->assertWritable('decrement');

        return parent::decrement($column, $amount, $extra);
    }

    public function incrementEach(array $columns, array $extra = [])
    {
        $this->assertWritable('incrementEach');

        return parent::incrementEach($columns, $extra);
    }

    public function decrementEach(array $columns, array $extra = [])
    {
        $this->assertWritable('decrementEach');

        return parent::decrementEach($columns, $extra);
    }

    public function delete()
    {
        $this->assertWritable('delete');

        return parent::delete();
    }

    public function forceDelete()
    {
        $this->assertWritable('forceDelete');

        return parent::forceDelete();
    }

    public function insert(array $values): bool
    {
        $this->assertWritable('insert');

        return $this->toBase()->insert($values);
    }

    public function insertOrIgnore(array $values): int
    {
        $this->assertWritable('insertOrIgnore');

        return $this->toBase()->insertOrIgnore($values);
    }

    public function insertGetId(array $values, $sequence = null): int|string
    {
        $this->assertWritable('insertGetId');

        return $this->toBase()->insertGetId($values, $sequence);
    }

    public function insertUsing(array $columns, $query): int
    {
        $this->assertWritable('insertUsing');

        return $this->toBase()->insertUsing($columns, $query);
    }

    public function insertOrIgnoreUsing(array $columns, $query): int
    {
        $this->assertWritable('insertOrIgnoreUsing');

        return $this->toBase()->insertOrIgnoreUsing($columns, $query);
    }

    public function updateOrInsert(array $attributes, array|callable $values = []): bool
    {
        $this->assertWritable('updateOrInsert');

        return $this->toBase()->updateOrInsert($attributes, $values);
    }

    private function assertWritable(string $operation): void
    {
        ReadOnlyModel::assertWritable($this->model::class, $operation);
    }
}
