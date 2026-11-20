<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Providers;

use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Users\Domain\Contracts\JournalSubjectResolver;

/** FR-34, wiring: where a Journal entry about an Offer opens (its view page), or nowhere once the Offer is gone (Users FR-31). */
final class OfferJournalSubject implements JournalSubjectResolver
{
    public function urlFor(int $subjectId): ?string
    {
        if (! OfferModel::query()->whereKey($subjectId)->exists()) {
            return null;
        }

        return OfferResource::getUrl('view', ['record' => $subjectId]);
    }
}
