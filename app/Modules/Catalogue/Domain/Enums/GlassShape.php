<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The shipped glass shapes a position can be drawn as (FR-15, D-54). The values are stored: never
 * rename them. Every outline lives in a 100 x 64 box and is flat on the sides that matter, so a
 * mirrored shape is visibly the other side.
 */
enum GlassShape: string implements HasLabel
{
    case Windscreen = 'windscreen';
    case Rear = 'rear';
    case SideDrop = 'side_drop';
    case SideFixed = 'side_fixed';
    case Roof = 'roof';
    case Accessory = 'accessory';
    case Unknown = 'unknown';

    public const string VIEW_BOX = '0 0 100 64';

    public function getLabel(): string
    {
        return __('catalogue::enums.glass_shape.'.$this->value);
    }

    /** The outline as an SVG path in the 100 x 64 box. */
    public function outline(): string
    {
        return match ($this) {
            self::Windscreen => 'M14 52 L26 10 H74 L86 52 Q50 58 14 52 Z',
            self::Rear => 'M12 50 Q50 58 88 50 L80 14 Q50 8 20 14 Z',
            self::SideDrop => 'M10 52 L26 14 Q28 10 32 10 H78 Q86 10 86 18 V52 Z',
            self::SideFixed => 'M18 52 V20 L72 12 L84 52 Z',
            self::Roof => 'M24 12 H76 Q88 12 88 24 V40 Q88 52 76 52 H24 Q12 52 12 40 V24 Q12 12 24 12 Z',
            self::Accessory => 'M24 14 H66 Q76 14 76 24 V34 Q76 44 66 44 H40 L30 54 V44 H24 Q14 44 14 34 V24 Q14 14 24 14 Z',
            self::Unknown => 'M16 12 H84 Q90 12 90 18 V46 Q90 52 84 52 H16 Q10 52 10 46 V18 Q10 12 16 12 Z',
        };
    }
}
