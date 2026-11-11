<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Exceptions\ThumbnailMappingRefused;
use App\Modules\Catalogue\Domain\ValueObjects\FillColour;
use PHPUnit\Framework\TestCase;

/** FR-15-FR-17. */
class ThumbnailMappingTest extends TestCase
{
    public function test_a_position_mapping_holds_a_shape_and_the_mirrored_flag(): void
    {
        $mapping = ThumbnailMapping::forPosition(1, Dictionary::Position, GlassShape::Rear, true);

        $this->assertSame(GlassShape::Rear, $mapping->shape());
        $this->assertTrue($mapping->isMirrored());
    }

    public function test_a_colour_mapping_accepts_the_opacity_bounds(): void
    {
        $this->assertSame(0, ThumbnailMapping::forColour(1, Dictionary::Colour, FillColour::from('#ffffff'), 0)->opacity());
        $this->assertSame(100, ThumbnailMapping::forColour(1, Dictionary::Colour, FillColour::from('#ffffff'), 100)->opacity());
    }

    public function test_it_refuses_an_opacity_out_of_range(): void
    {
        foreach ([-1, 101] as $opacity) {
            try {
                ThumbnailMapping::forColour(1, Dictionary::Colour, FillColour::from('#ffffff'), $opacity);
                $this->fail('Opacity '.$opacity.' was accepted.');
            } catch (ThumbnailMappingRefused $e) {
                $this->assertSame('catalogue::exceptions.thumbnail.opacity_is_out_of_range', $e->translationKey);
            }
        }
    }

    public function test_it_refuses_a_mapping_for_a_value_of_the_wrong_dictionary(): void
    {
        $this->expectException(ThumbnailMappingRefused::class);

        ThumbnailMapping::forEquipment(1, Dictionary::Colour, Pictogram::Camera);
    }

    public function test_it_refuses_a_fill_that_is_not_a_hex_colour(): void
    {
        $this->expectException(ThumbnailMappingRefused::class);

        FillColour::from('red"/><script>');
    }

    public function test_replacing_keeps_the_identity_and_takes_the_new_drawing(): void
    {
        $existing = ThumbnailMapping::reconstitute(5, 1, Dictionary::Position, GlassShape::Rear, false, null, null, null);

        $existing->replaceWith(ThumbnailMapping::forPosition(1, Dictionary::Position, GlassShape::Roof, true));

        $this->assertSame(5, $existing->id());
        $this->assertSame(GlassShape::Roof, $existing->shape());
        $this->assertTrue($existing->isMirrored());
    }

    public function test_it_refuses_to_take_the_drawing_of_another_value(): void
    {
        $existing = ThumbnailMapping::reconstitute(5, 1, Dictionary::Position, GlassShape::Rear, false, null, null, null);

        $this->expectException(ThumbnailMappingRefused::class);

        $existing->replaceWith(ThumbnailMapping::forPosition(2, Dictionary::Position, GlassShape::Roof, true));
    }
}
