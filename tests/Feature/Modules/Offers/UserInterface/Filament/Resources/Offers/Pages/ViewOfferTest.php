<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_owner_sees_the_piece_the_glass_model_summary_and_their_private_notes(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $glassModel = GlassModelModelFactory::new()->withMainEurocode('1234ACS')->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withGlassModel($glassModel->id)
            ->create(['description' => 'Szyba w dobrym stanie', 'private_notes' => 'Kupiona od Jana']);

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
            ->assertSee($offer->number)
            ->assertSee('1234ACS')
            ->assertSee('Szyba w dobrym stanie')
            ->assertSee('Kupiona od Jana');
    }

    public function test_the_glass_model_summary_draws_the_thumbnail(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $glassModel = GlassModelModelFactory::new()->withMainEurocode('1234ACS')->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withGlassModel($glassModel->id)->create();

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
            ->assertSeeHtml('glass-thumbnail__shape')
            ->assertSeeHtml('d="'.GlassShape::Unknown->outline().'"');
    }

    public function test_a_withdrawn_glass_model_is_still_shown(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);
        $glassModel = GlassModelModelFactory::new()->withMainEurocode('9999XYZ')->withdrawn()->create();
        $offer = OfferModelFactory::new()->withGlassModel($glassModel->id)->create();

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
            ->assertSee('9999XYZ')
            ->assertSee(__('offers::resources.offer.glass_model.withdrawn'));
    }

    public function test_an_admin_sees_another_sellers_private_notes(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->create(['private_notes' => 'Tajna notatka']);

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])->assertSee('Tajna notatka');
    }

    public function test_a_new_piece_is_shown_with_five_stars(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create(['condition' => 'new']);

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])->assertSee('★★★★★');
    }

    public function test_a_seller_cannot_view_another_sellers_offer(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $offer = OfferModelFactory::new()->create();

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])->assertNotFound();
    }

    public function test_the_owner_sees_both_galleries_and_an_admin_too(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)
            ->withPublicPhotos(["offers/public/{$owner->id}/a.jpg"])
            ->withPrivatePhotos(["offers/private/{$owner->id}/x.jpg"])
            ->create();

        foreach ([$owner, UserModel::factory()->admin()->create()] as $viewer) {
            $this->actingAs($viewer);

            Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
                ->assertSee('Zdjęcia prywatne')
                ->assertSee('offers/public/'.$owner->id.'/a.jpg')
                ->assertSee("offers/{$offer->id}/private-photos/0");
        }
    }
}
