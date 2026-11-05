<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

enum ThingStatus: string
{
    case Draft = 'draft';
    case Active = 'active';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Szkic',
            self::Active => 'Aktywna',
        };
    }
}
