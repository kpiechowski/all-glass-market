<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Exceptions\ThumbnailMappingRefused;
use App\Modules\Catalogue\Domain\ValueObjects\FillColour;

/**
 * How one dictionary value is drawn on a thumbnail (FR-15-FR-17, D-54-D-56): a position gets a shape
 * and a mirrored flag, a colour a fill and an opacity, an equipment value a pictogram. A value with
 * no mapping is drawn by the defaults of FR-18. Not part of any Glass model (RULE-12).
 */
final class ThumbnailMapping
{
    private function __construct(
        private ?int $id,
        private readonly int $dictionaryValueId,
        private readonly Dictionary $dictionary,
        private ?GlassShape $shape,
        private bool $mirrored,
        private ?FillColour $fill,
        private ?int $opacity,
        private ?Pictogram $pictogram,
    ) {}

    /** @param  Dictionary  $dictionary  the dictionary of the value, which the handler read */
    public static function forPosition(int $valueId, Dictionary $dictionary, GlassShape $shape, bool $mirrored): self
    {
        self::ensureDictionary($dictionary, Dictionary::Position);

        return new self(null, $valueId, $dictionary, $shape, $mirrored, null, null, null);
    }

    /** @param  int  $opacity  percent, 0-100 */
    public static function forColour(int $valueId, Dictionary $dictionary, FillColour $fill, int $opacity): self
    {
        self::ensureDictionary($dictionary, Dictionary::Colour);

        if ($opacity < 0 || $opacity > 100) {
            throw ThumbnailMappingRefused::opacityIsOutOfRange();
        }

        return new self(null, $valueId, $dictionary, null, false, $fill, $opacity, null);
    }

    public static function forEquipment(int $valueId, Dictionary $dictionary, Pictogram $pictogram): self
    {
        self::ensureDictionary($dictionary, Dictionary::Equipment);

        return new self(null, $valueId, $dictionary, null, false, null, null, $pictogram);
    }

    /** @internal for the mapper */
    public static function reconstitute(
        int $id,
        int $dictionaryValueId,
        Dictionary $dictionary,
        ?GlassShape $shape,
        bool $mirrored,
        ?FillColour $fill,
        ?int $opacity,
        ?Pictogram $pictogram,
    ): self {
        return new self($id, $dictionaryValueId, $dictionary, $shape, $mirrored, $fill, $opacity, $pictogram);
    }

    /** Takes the drawing of a new mapping of the same value, keeping this one's identity. */
    public function replaceWith(self $other): void
    {
        if ($other->dictionaryValueId !== $this->dictionaryValueId) {
            throw ThumbnailMappingRefused::wrongDictionary();
        }

        $this->shape = $other->shape;
        $this->mirrored = $other->mirrored;
        $this->fill = $other->fill;
        $this->opacity = $other->opacity;
        $this->pictogram = $other->pictogram;
    }

    private static function ensureDictionary(Dictionary $actual, Dictionary $expected): void
    {
        if ($actual !== $expected) {
            throw ThumbnailMappingRefused::wrongDictionary();
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function dictionaryValueId(): int
    {
        return $this->dictionaryValueId;
    }

    public function dictionary(): Dictionary
    {
        return $this->dictionary;
    }

    public function shape(): ?GlassShape
    {
        return $this->shape;
    }

    public function isMirrored(): bool
    {
        return $this->mirrored;
    }

    public function fill(): ?FillColour
    {
        return $this->fill;
    }

    public function opacity(): ?int
    {
        return $this->opacity;
    }

    public function pictogram(): ?Pictogram
    {
        return $this->pictogram;
    }
}
