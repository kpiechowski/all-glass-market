<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Contracts\Mapper;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<Widget> */
final class WidgetMapper implements Mapper
{
    public function toEntity(Model $model): Widget
    {
        return Widget::reconstitute($model->id, $model->name);
    }

    public function toRow(object $entity): array
    {
        return ['name' => $entity->name()];
    }
}
