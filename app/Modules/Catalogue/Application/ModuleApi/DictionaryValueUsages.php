<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\DictionaryValueUsage;
use App\Modules\Catalogue\Domain\Enums\Dictionary;

/**
 * Where "delete only while unused" (FR-24) learns the uses of a dictionary value. Whoever keeps
 * records referring to a value registers a `DictionaryValueUsage` in its provider's boot():
 *
 *     $this->app->make(DictionaryValueUsages::class)->register(new VehicleDictionaryUsage);
 *
 * Tickets 02 (vehicle make, body), 03 (Glass model position, colour, equipment) and 05 add their
 * uses inside Catalogue; 09 adds the offers' use of producers. Registration lives in memory: a
 * module that is not installed registers nothing, so its uses do not exist.
 */
final class DictionaryValueUsages
{
    /** @var list<DictionaryValueUsage> */
    private array $usages = [];

    public function register(DictionaryValueUsage $usage): void
    {
        $this->usages[] = $usage;
    }

    /** @return list<DictionaryUse> every use of the value, empty when nothing uses it */
    public function of(Dictionary $dictionary, int $valueId): array
    {
        $uses = [];

        foreach ($this->usages as $usage) {
            foreach ($usage->usesOf($dictionary, $valueId) as $use) {
                if ($use->count > 0) {
                    $uses[] = $use;
                }
            }
        }

        return $uses;
    }
}
