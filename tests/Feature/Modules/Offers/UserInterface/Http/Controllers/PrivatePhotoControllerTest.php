<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Http\Controllers;

use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Http\Controllers\PrivatePhotoController;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** FR-05, D-49: a private photo reaches the owner and admins only, also by URL. */
class PrivatePhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function offerWithPrivatePhoto(UserModel $owner): OfferModel
    {
        Storage::fake(PrivatePhotoController::DISK);
        Storage::disk(PrivatePhotoController::DISK)->put("offers/private/{$owner->id}/x.jpg", 'secret-bytes');

        return OfferModelFactory::new()->ownedBy($owner->id)->withPrivatePhotos(["offers/private/{$owner->id}/x.jpg"])->create();
    }

    private function url(OfferModel $offer, int $position = 0): string
    {
        return route(PrivatePhotoController::ROUTE, ['offer' => $offer->id, 'position' => $position]);
    }

    public function test_the_owner_gets_their_private_photo(): void
    {
        $owner = UserModel::factory()->create();
        $offer = $this->offerWithPrivatePhoto($owner);

        $this->actingAs($owner)->get($this->url($offer))->assertOk();
    }

    public function test_an_admin_gets_any_private_photo(): void
    {
        $offer = $this->offerWithPrivatePhoto(UserModel::factory()->create());

        $this->actingAs(UserModel::factory()->admin()->create())->get($this->url($offer))->assertOk();
    }

    public function test_another_seller_gets_a_404_for_it(): void
    {
        $offer = $this->offerWithPrivatePhoto(UserModel::factory()->create());

        $this->actingAs(UserModel::factory()->create())->get($this->url($offer))->assertNotFound();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $offer = $this->offerWithPrivatePhoto(UserModel::factory()->create());

        $this->get($this->url($offer))->assertRedirect();
    }

    public function test_a_position_with_no_photo_is_a_404(): void
    {
        $owner = UserModel::factory()->create();
        $offer = $this->offerWithPrivatePhoto($owner);

        $this->actingAs($owner)->get($this->url($offer, 5))->assertNotFound();
    }

    public function test_the_private_disk_gives_no_public_url_to_the_file(): void
    {
        $this->assertNotSame('public', PrivatePhotoController::DISK);
        $this->assertArrayNotHasKey('url', config('filesystems.disks.'.PrivatePhotoController::DISK));
    }
}
