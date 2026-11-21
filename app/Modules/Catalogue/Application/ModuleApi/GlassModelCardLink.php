<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

/**
 * One link a module adds to a Glass model card, for example "3 ofert" (FR-04).
 */
final readonly class GlassModelCardLink
{
    public function __construct(
        /** The finished text, translated by the module that provides it. */
        public string $label,
        /** Where the link leads. */
        public string $url,
    ) {}
}
