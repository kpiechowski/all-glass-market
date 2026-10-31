<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

final readonly class StoreWidgetCommand
{
    public function __construct(public string $name, public bool $fail) {}
}
