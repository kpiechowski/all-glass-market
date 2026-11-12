<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Enums;

/** How much test data `seed-katalog` loads. The counts are targets for hand-checking the panel, not a contract. */
enum CatalogueSeedSize: string
{
    case Small = 's';
    case Medium = 'm';
    case Large = 'l';

    public function glassModels(): int
    {
        return match ($this) {
            self::Small => 20,
            self::Medium => 200,
            self::Large => 2000,
        };
    }

    public function vehicles(): int
    {
        return match ($this) {
            self::Small => 15,
            self::Medium => 80,
            self::Large => 400,
        };
    }
}
