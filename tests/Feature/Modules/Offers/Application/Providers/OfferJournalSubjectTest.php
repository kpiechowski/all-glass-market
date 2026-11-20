<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Providers;

use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-34: a Journal entry about an Offer opens the Offer's view page; once the Offer is gone it opens nothing. */
class OfferJournalSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_offer_kind_resolves_to_the_view_page_and_a_missing_offer_to_nothing(): void
    {
        Filament::setCurrentPanel('admin');
        $offer = OfferModelFactory::new()->create();
        $subjects = app(JournalSubjects::class);

        $this->assertStringEndsWith('/offers/'.$offer->id, $subjects->urlFor('offer', $offer->id));
        $this->assertNull($subjects->urlFor('offer', 999999));
    }
}
