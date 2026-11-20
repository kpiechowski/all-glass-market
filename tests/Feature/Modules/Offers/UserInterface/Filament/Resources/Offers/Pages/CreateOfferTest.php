<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\CreateOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_creates_a_draft_with_a_glass_model_through_the_command(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $glassModel = GlassModelModelFactory::new()->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'glass_model_id' => $glassModel->id,
                'producer_id' => $producer->id,
                'year' => 2018,
                'condition' => 'used',
                'rating' => 4,
                'description' => 'Opis',
                'private_notes' => 'Notatka',
                'price' => '1234.50',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = OfferModel::firstOrFail();
        $this->assertSame($seller->id, $offer->owner_id);
        $this->assertSame(OfferStatus::Draft, $offer->status);
        $this->assertSame($glassModel->id, $offer->glass_model_id);
        $this->assertSame(OfferCondition::Used, $offer->condition);
        $this->assertSame(4, $offer->rating);
        $this->assertSame(123450, $offer->price_grosze);
        $this->assertMatchesRegularExpression('/^AG\d{8}$/', $offer->number);
    }

    public function test_an_admin_creates_a_draft_with_a_hint_and_owns_it(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateOffer::class)
            ->fillForm(['unknown_model' => true, 'identification_hint' => 'VIN WVW123', 'condition' => 'new'])
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = OfferModel::firstOrFail();
        $this->assertSame($admin->id, $offer->owner_id);
        $this->assertSame('VIN WVW123', $offer->identification_hint);
        $this->assertNull($offer->glass_model_id);
    }

    public function test_the_rating_field_appears_only_for_a_used_or_outlet_piece(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(CreateOffer::class)
            ->fillForm(['condition' => 'new'])
            ->assertFormFieldIsHidden('rating')
            ->fillForm(['condition' => 'outlet'])
            ->assertFormFieldIsVisible('rating');
    }

    public function test_a_withdrawn_glass_model_chosen_with_a_stale_picker_is_refused_and_nothing_is_saved(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();

        Livewire::test(CreateOffer::class)
            ->fillForm(['glass_model_id' => $withdrawn->id])
            ->call('create');

        $this->assertSame(0, OfferModel::count());
    }

    public function test_picking_a_glass_model_drops_the_hint_and_the_unknown_model_action_drops_the_model(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $glassModel = GlassModelModelFactory::new()->create();

        Livewire::test(CreateOffer::class)
            ->fillForm(['unknown_model' => true, 'identification_hint' => 'OEM 1'])
            ->callAction(TestAction::make('pickGlassModel')->schemaComponent('glass'), ['glass_model_id' => $glassModel->id])
            ->assertSchemaStateSet(['glass_model_id' => $glassModel->id, 'identification_hint' => null])
            ->callAction(TestAction::make('unknownGlassModel')->schemaComponent('glass'))
            ->assertSchemaStateSet(['glass_model_id' => null, 'unknown_model' => true]);
    }
}
