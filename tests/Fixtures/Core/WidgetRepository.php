<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Abstracts\EloquentRepository;

/** @extends EloquentRepository<Widget> */
final class WidgetRepository extends EloquentRepository
{
    public function __construct()
    {
        parent::__construct(WidgetModel::class, new WidgetMapper);
    }

    public function named(string $name): ?Widget
    {
        $model = $this->query()->where('name', $name)->first();

        return $model === null ? null : (new WidgetMapper)->toEntity($model);
    }
}
