<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;

/**
 * FR-20: the main Eurocode proposed for a Glass model created in place, from the text the admin was
 * searching with (an identification hint, a typed fragment). RULE-03 normalisation only: the result
 * may still be empty or too long, which the admin sees and corrects before the model is created.
 */
final class EurocodeSuggestions
{
    public function from(?string $text): string
    {
        return Eurocode::normalise((string) $text);
    }
}
