<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The shipped equipment pictograms (FR-17, D-56); `Universal` is what an unmapped equipment value
 * shows (FR-18). The values are stored: never rename them. Each one is simple stroke art in a
 * 24 x 24 box, drawn with `currentColor`.
 */
enum Pictogram: string implements HasLabel
{
    case Universal = 'universal';
    case Heated = 'heated';
    case RainSensor = 'rain_sensor';
    case Camera = 'camera';
    case Antenna = 'antenna';
    case Acoustic = 'acoustic';
    case HeadUpDisplay = 'head_up_display';
    case Sensor = 'sensor';

    public const string VIEW_BOX = '0 0 24 24';

    public function getLabel(): string
    {
        return __('catalogue::enums.pictogram.'.$this->value);
    }

    /** The strokes as SVG elements in the 24 x 24 box. */
    public function markup(): string
    {
        return match ($this) {
            self::Universal => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="2"/>',
            self::Heated => '<path d="M8 4c-2 3 2 5 0 8s2 5 0 8"/><path d="M16 4c-2 3 2 5 0 8s2 5 0 8"/>',
            self::RainSensor => '<path d="M12 3c4 5 6 8 6 11a6 6 0 0 1-12 0c0-3 2-6 6-11z"/>',
            self::Camera => '<rect x="3" y="7" width="18" height="12" rx="2"/><circle cx="12" cy="13" r="3"/>',
            self::Antenna => '<path d="M12 21V11"/><circle cx="12" cy="9" r="2"/><path d="M8 5a6 6 0 0 0 0 8M16 5a6 6 0 0 1 0 8"/>',
            self::Acoustic => '<path d="M4 10v4h4l5 4V6l-5 4z"/><path d="M16 9a4 4 0 0 1 0 6"/>',
            self::HeadUpDisplay => '<path d="M3 8l9-4 9 4"/><path d="M6 13h12M8 17h8"/>',
            self::Sensor => '<circle cx="12" cy="12" r="2"/><path d="M7 7a7 7 0 0 0 0 10M17 7a7 7 0 0 1 0 10"/>',
        };
    }
}
