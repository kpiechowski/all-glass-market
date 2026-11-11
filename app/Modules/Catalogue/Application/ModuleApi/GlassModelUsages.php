<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\GlassModelUsage;

/**
 * Where "delete a Glass model only while unused" (FR-24) learns the uses of a model. Offers
 * registers a `GlassModelUsage` in its provider's boot() (ticket 09):
 *
 *     $this->app->make(GlassModelUsages::class)->register(new OfferGlassModelUsage);
 *
 * Catalogue registers none: nothing inside it uses a Glass model yet.
 */
final class GlassModelUsages
{
    /** @var list<GlassModelUsage> */
    private array $usages = [];

    public function register(GlassModelUsage $usage): void
    {
        $this->usages[] = $usage;
    }

    /** @return list<DictionaryUse> every use of the model, empty when nothing uses it */
    public function of(int $glassModelId): array
    {
        $uses = [];

        foreach ($this->usages as $usage) {
            foreach ($usage->usesOf($glassModelId) as $use) {
                if ($use->count > 0) {
                    $uses[] = $use;
                }
            }
        }

        return $uses;
    }
}
