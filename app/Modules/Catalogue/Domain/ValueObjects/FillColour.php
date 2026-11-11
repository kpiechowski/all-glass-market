<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\ThumbnailMappingRefused;

/** FR-16: a fill colour as #rrggbb, lowercased. Nothing else reaches an SVG attribute. */
final readonly class FillColour
{
    private function __construct(public string $value) {}

    public static function from(string $colour): self
    {
        $colour = strtolower(trim($colour));

        if (preg_match('/^#[0-9a-f]{6}$/', $colour) !== 1) {
            throw ThumbnailMappingRefused::fillIsNotAColour();
        }

        return new self($colour);
    }
}
