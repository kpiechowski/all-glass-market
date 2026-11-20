<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\EditOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EditOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_owner_edits_their_draft_through_the_command_and_the_price_round_trips(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withHint('stara')->priced(9950)->create(['number' => 'AG00000009']);

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->assertSchemaStateSet(['price' => '99.50', 'identification_hint' => 'stara'])
            ->fillForm(['identification_hint' => 'nowa', 'price' => '120'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $offer->fresh();
        $this->assertSame('nowa', $fresh->identification_hint);
        $this->assertSame(12000, $fresh->price_grosze);
        $this->assertSame(OfferStatus::Draft, $fresh->status);
        $this->assertSame('AG00000009', $fresh->number);
        $this->assertSame($owner->id, $fresh->owner_id);
    }

    public function test_an_admin_edits_another_sellers_offer_and_the_owner_stays(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withHint('stara')->create();

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['identification_hint' => 'poprawiona'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('poprawiona', $offer->fresh()->identification_hint);
        $this->assertSame($owner->id, $offer->fresh()->owner_id);
    }

    public function test_a_seller_cannot_open_another_sellers_offer_for_editing(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $offer = OfferModelFactory::new()->create();

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])->assertNotFound();
    }

    public function test_uploaded_photos_travel_as_paths_in_the_command_and_keep_their_order(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create();

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm([
                'public_photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
                'private_photos' => [UploadedFile::fake()->image('p.jpg')],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $offer->fresh();
        $this->assertCount(2, $fresh->public_photos);
        $this->assertCount(1, $fresh->private_photos);
        $this->assertStringStartsWith("offers/public/{$owner->id}/", $fresh->public_photos[0]);
        $this->assertStringStartsWith("offers/private/{$owner->id}/", $fresh->private_photos[0]);
        Storage::disk('public')->assertExists($fresh->public_photos);
        Storage::disk('local')->assertExists($fresh->private_photos);
    }

    public function test_dragging_changes_the_order_of_the_public_photos(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $a = "offers/public/{$owner->id}/a.jpg";
        $b = "offers/public/{$owner->id}/b.jpg";
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withPublicPhotos([$a, $b])->create();

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['public_photos' => [$b, $a]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$b, $a], $offer->fresh()->public_photos);
    }
}
