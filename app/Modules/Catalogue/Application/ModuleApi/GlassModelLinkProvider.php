<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

/**
 * The seam of the link on a Glass model card (FR-04, D-37): Catalogue knows nothing of Offers, so
 * whoever keeps records about a Glass model (Offers: "N ofert") implements this and registers it with
 * `GlassModelLinks` in its provider's boot(). It answers for a whole page of cards in one call.
 */
interface GlassModelLinkProvider
{
    /**
     * @param  list<int>  $glassModelIds  the Glass models of one page
     * @return array<int, GlassModelCardLink> by Glass model id; a model without anything to link to is left out
     */
    public function linksFor(array $glassModelIds): array;
}
