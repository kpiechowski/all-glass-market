<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\PresentChange;

/** One value as shown. `removed` marks a reference whose record was deleted or is gone (D-40). */
final readonly class PresentedValue
{
    public function __construct(
        public string $text,
        public bool $removed = false,
    ) {}
}
