<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Services;

use App\Modules\Catalogue\Application\ModuleApi\CatalogueDictionaries;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\Rating;

/**
 * Shared by the create and update handlers: builds the value objects from a command's primitives
 * and gathers, through Catalogue's module interface only, the facts about the chosen Glass model
 * and producer. The picker's id is client state, so the model must exist and not be withdrawn,
 * and the producer must be an active one (FR-02, FR-03).
 */
final class OfferContentAssembler
{
    public function __construct(
        private readonly GlassModelSummaries $glassModels,
        private readonly CatalogueDictionaries $dictionaries,
    ) {}

    public function assemble(
        ?int $glassModelId,
        ?string $identificationHint,
        ?int $producerId,
        ?int $year,
        ?string $condition,
        ?int $rating,
        ?string $description,
        ?string $privateNotes,
        ?int $priceGrosze,
    ): OfferContent {
        $offerCondition = $condition === null ? null : (OfferCondition::tryFrom($condition) ?? throw OfferRefused::conditionIsUnknown());

        return OfferContent::from(
            GlassReference::from($glassModelId, $identificationHint),
            $producerId,
            $year,
            $offerCondition,
            $rating === null ? null : Rating::from($rating),
            $description,
            $privateNotes,
            $priceGrosze === null ? null : EnteredPrice::fromGrosze($priceGrosze),
        );
    }

    public function glassModelCanBePicked(OfferContent $content): bool
    {
        $id = $content->glass->glassModelId;

        if ($id === null) {
            return true;
        }

        $summary = $this->glassModels->of($id);

        return $summary !== null && ! $summary->withdrawn;
    }

    public function producerCanBePicked(OfferContent $content): bool
    {
        if ($content->producerId === null) {
            return true;
        }

        foreach ($this->dictionaries->producers() as $producer) {
            if ($producer->id === $content->producerId) {
                return true;
            }
        }

        return false;
    }
}
