<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\Policies;

use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Policies\ThumbnailDrawingPolicy;
use App\Modules\Catalogue\Domain\ValueObjects\FillColour;
use App\Modules\Catalogue\Domain\ValueObjects\ThumbnailSettings;
use PHPUnit\Framework\TestCase;

/** FR-18, D-54-D-56. */
class ThumbnailDrawingPolicyTest extends TestCase
{
    private function settings(): ThumbnailSettings
    {
        return ThumbnailSettings::of([
            ThumbnailMapping::forPosition(1, Dictionary::Position, GlassShape::SideDrop, true),
            ThumbnailMapping::forColour(2, Dictionary::Colour, FillColour::from('#1A8A3C'), 60),
            ThumbnailMapping::forEquipment(3, Dictionary::Equipment, Pictogram::Heated),
        ]);
    }

    public function test_it_draws_the_mapped_shape_mirrored_with_the_mapped_fill(): void
    {
        $drawing = ThumbnailDrawingPolicy::draw($this->settings(), 1, 2, []);

        $this->assertSame(GlassShape::SideDrop, $drawing->shape);
        $this->assertTrue($drawing->mirrored);
        $this->assertSame('#1a8a3c', $drawing->fill);
        $this->assertSame(60, $drawing->opacity);
    }

    public function test_an_unmapped_position_draws_the_unknown_shape(): void
    {
        $drawing = ThumbnailDrawingPolicy::draw($this->settings(), 99, null, []);

        $this->assertSame(GlassShape::Unknown, $drawing->shape);
        $this->assertFalse($drawing->mirrored);
    }

    public function test_no_position_draws_the_unknown_shape(): void
    {
        $this->assertSame(GlassShape::Unknown, ThumbnailDrawingPolicy::draw($this->settings(), null, null, [])->shape);
    }

    public function test_no_colour_or_an_unmapped_colour_draws_clear_glass(): void
    {
        foreach ([null, 99] as $colourId) {
            $drawing = ThumbnailDrawingPolicy::draw($this->settings(), 1, $colourId, []);

            $this->assertSame(ThumbnailDrawingPolicy::CLEAR_FILL, $drawing->fill);
            $this->assertSame(ThumbnailDrawingPolicy::CLEAR_OPACITY, $drawing->opacity);
        }
    }

    public function test_equipment_is_a_row_of_pictograms_named_in_order_with_the_universal_one_for_unmapped(): void
    {
        $drawing = ThumbnailDrawingPolicy::draw($this->settings(), 1, 2, [7 => 'Kamera', 3 => 'Podgrzewana']);

        $this->assertSame(
            [['pictogram' => Pictogram::Universal, 'label' => 'Kamera'], ['pictogram' => Pictogram::Heated, 'label' => 'Podgrzewana']],
            $drawing->pictograms,
        );
    }

    public function test_without_settings_everything_is_default(): void
    {
        $drawing = ThumbnailDrawingPolicy::draw(ThumbnailSettings::empty(), 1, 2, [3 => 'Czujnik']);

        $this->assertSame(GlassShape::Unknown, $drawing->shape);
        $this->assertSame(ThumbnailDrawingPolicy::CLEAR_FILL, $drawing->fill);
        $this->assertSame(Pictogram::Universal, $drawing->pictograms[0]['pictogram']);
    }
}
