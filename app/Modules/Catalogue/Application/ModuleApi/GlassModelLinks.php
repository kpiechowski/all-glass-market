<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

/**
 * Where the Glass model card learns the links other modules add to it. A module registers a
 * `GlassModelLinkProvider` in its provider's boot():
 *
 *     $this->app->make(GlassModelLinks::class)->register(new OfferGlassModelLinks);
 *
 * One call answers for a whole page of cards, so the list asks once instead of once per card.
 */
final class GlassModelLinks
{
    /** @var list<GlassModelLinkProvider> */
    private array $providers = [];

    public function register(GlassModelLinkProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @param  list<int>  $glassModelIds  the Glass models of one page
     * @return array<int, list<GlassModelCardLink>> by Glass model id; a model without links is left out
     */
    public function ofMany(array $glassModelIds): array
    {
        $links = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->linksFor($glassModelIds) as $id => $link) {
                $links[$id][] = $link;
            }
        }

        return $links;
    }
}
