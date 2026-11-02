<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;

final class WidgetModel extends ReadOnlyModel
{
    protected $table = 'widgets';

    protected $guarded = [];
}
