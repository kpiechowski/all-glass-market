<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The way a Glass model was found by the candidate search (D-49). The declaration order is the
 * result order: Eurocode hits, then OEM hits, then vehicle hits. The values are stored nowhere
 * but travel to Offers: never rename them.
 */
enum MatchRoute: string implements HasLabel
{
    case Eurocode = 'eurocode';
    case Oem = 'oem';
    case Vehicle = 'vehicle';

    public function getLabel(): string
    {
        return __('catalogue::enums.match_route.'.$this->value);
    }
}
