<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Core\Domain\Actor;
use App\Modules\Audit\UserInterface\Filament\RelationManagers\RecordHistoryRelationManager;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\ApproveOffer\ApproveOfferCommand;
use App\Modules\Offers\Application\Commands\UpdateOffer\UpdateOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-12: the History tab and "Zmiany od ostatniego zatwierdzenia", admins only. */
class ViewOfferHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    private function open(OfferModel $offer): Testable
    {
        return Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()]);
    }

    /** Approved once, then the seller changes the Glass model and the description: Pending again. */
    private function approvedThenEditedBySeller(UserModel $owner, UserModel $admin): OfferModel
    {
        $first = GlassModelModelFactory::new()->create();
        $second = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->sendable()->pending()->withGlassModel($first->id)->ownedBy($owner->id)->create();

        app(CommandBus::class)->send(new ApproveOfferCommand(Actor::user($admin->id), $offer->id));
        app(CommandBus::class)->send(new UpdateOfferCommand(
            actor: Actor::user($owner->id),
            offerId: $offer->id,
            glassModelId: $second->id,
            producerId: $offer->producer_id,
            year: $offer->year,
            condition: $offer->condition?->value,
            rating: $offer->rating,
            description: 'Zupełnie nowy opis',
            privateNotes: $offer->private_notes,
            priceGrosze: $offer->price_grosze,
        ));

        $offer = $offer->fresh();
        $this->assertSame(OfferStatus::Pending, $offer->status);

        return $offer;
    }

    public function test_the_block_lists_exactly_the_changes_after_the_last_approval(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = $this->approvedThenEditedBySeller($owner, $admin);
        $this->actingAs($admin);

        $html = $this->open($offer)
            ->assertSee(__('offers::resources.offer.sections.changes_since_approval'))
            ->assertSee(__('offers::audit.offer.fields.description'))
            ->assertSee(__('offers::audit.offer.fields.glass_model'))
            ->assertSee('Zupełnie nowy opis')
            ->html();

        $block = substr($html, (int) strpos($html, __('offers::resources.offer.sections.changes_since_approval')));
        $block = substr($block, 0, (int) strpos($block, __('offers::resources.offer.fields.number')));
        $status = __('offers::audit.offer.fields.status');

        $this->assertStringNotContainsString($status.':', $block, 'no status line, neither the approval\'s nor the seller\'s');
        $this->assertStringNotContainsString(__('offers::audit.offer.fields.last_approved_at'), $block);
    }

    public function test_a_glass_model_change_shows_the_main_eurocodes(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = $this->approvedThenEditedBySeller($owner, $admin);
        $this->actingAs($admin);

        $summaries = app(GlassModelSummaries::class);
        $new = $summaries->of($offer->glass_model_id)->mainEurocode;

        $this->open($offer)->assertSee($new);
    }

    public function test_there_is_no_block_for_an_offer_never_approved(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);
        $offer = OfferModelFactory::new()->pending()->create();

        $this->open($offer)->assertDontSee(__('offers::resources.offer.sections.changes_since_approval'));
    }

    public function test_there_is_no_block_once_the_offer_is_no_longer_pending(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);
        $offer = OfferModelFactory::new()->status(OfferStatus::Active)->create(['last_approved_at' => now()->subDay()]);

        $this->open($offer)->assertDontSee(__('offers::resources.offer.sections.changes_since_approval'));
    }

    public function test_a_seller_sees_neither_the_block_nor_the_history_tab(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = $this->approvedThenEditedBySeller($owner, $admin);
        $this->actingAs($owner);

        $this->open($offer)
            ->assertDontSee(__('offers::resources.offer.sections.changes_since_approval'))
            ->assertDontSeeLivewire(RecordHistoryRelationManager::class);
    }

    public function test_an_admin_gets_the_history_tab(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->create();
        $this->actingAs($admin);

        $this->open($offer)->assertSeeLivewire(RecordHistoryRelationManager::class);
    }
}
