<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Modules\Catalogue\Application\Commands\DeleteDictionaryValue\DeleteDictionaryValueCommand;
use App\Modules\Catalogue\Application\Commands\DeleteGlassModel\DeleteGlassModelCommand;
use App\Modules\Catalogue\Application\Commands\WithdrawDictionaryValue\WithdrawDictionaryValueCommand;
use App\Modules\Catalogue\Application\Commands\WithdrawGlassModel\WithdrawGlassModelCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-25: a Glass model or a producer any Offer refers to, the Trash included, is in use. */
class OfferUsagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_a_glass_model_used_only_by_a_trashed_offer_cannot_be_deleted_but_can_be_withdrawn(): void
    {
        $model = GlassModelModelFactory::new()->create();
        OfferModelFactory::new()->withGlassModel($model->id)->inTrash()->create();

        try {
            app(CommandBus::class)->send(new DeleteGlassModelCommand($this->admin(), $model->id));
            $this->fail('The delete was not refused.');
        } catch (GlassModelRefused) {
            $this->assertNotNull(GlassModelModel::query()->find($model->id));
        }

        app(CommandBus::class)->send(new WithdrawGlassModelCommand($this->admin(), $model->id));

        $this->assertTrue((bool) $model->fresh()->withdrawn);
    }

    public function test_a_glass_model_without_offers_can_be_deleted(): void
    {
        $model = GlassModelModelFactory::new()->create();
        OfferModelFactory::new()->withGlassModel(GlassModelModelFactory::new()->create()->id)->create();

        app(CommandBus::class)->send(new DeleteGlassModelCommand($this->admin(), $model->id));

        $this->assertNull(GlassModelModel::query()->find($model->id));
    }

    public function test_a_producer_used_only_by_a_trashed_offer_cannot_be_deleted_but_can_be_withdrawn(): void
    {
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        OfferModelFactory::new()->inTrash()->create(['producer_id' => $producer->id]);

        try {
            app(CommandBus::class)->send(new DeleteDictionaryValueCommand($this->admin(), $producer->id));
            $this->fail('The delete was not refused.');
        } catch (DictionaryValueRefused) {
            $this->assertNotNull(DictionaryValueModel::query()->find($producer->id));
        }

        app(CommandBus::class)->send(new WithdrawDictionaryValueCommand($this->admin(), $producer->id));

        $this->assertTrue((bool) $producer->fresh()->withdrawn);
    }

    public function test_an_unused_producer_can_be_deleted(): void
    {
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        OfferModelFactory::new()->create(['producer_id' => DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create()->id]);

        app(CommandBus::class)->send(new DeleteDictionaryValueCommand($this->admin(), $producer->id));

        $this->assertNull(DictionaryValueModel::query()->find($producer->id));
    }
}
