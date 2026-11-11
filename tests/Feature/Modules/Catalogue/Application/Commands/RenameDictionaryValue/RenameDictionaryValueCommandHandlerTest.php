<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\RenameDictionaryValue;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\RenameDictionaryValue\RenameDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Events\ProducerChanged;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-12, FR-14, RULE-08, §9 Producer changed. */
class RenameDictionaryValueCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, DictionaryValueModel $value, string $name): void
    {
        app(CommandBus::class)->send(new RenameDictionaryValueCommand($actor, $value->id, $name));
    }

    public function test_an_admin_renames_a_value(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $value = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();

        $this->send(Actor::user($admin->id), $value, 'Butelkowy');

        $this->assertSame('Butelkowy', $value->fresh()->name);
        $this->assertSame('butelkowy', $value->fresh()->name_key);
    }

    public function test_renaming_a_producer_announces_it(): void
    {
        Event::fake([ProducerChanged::class]);
        $admin = UserModel::factory()->admin()->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Pilkington')->create();

        $this->send(Actor::user($admin->id), $producer, 'Pilkington Group');

        Event::assertDispatched(ProducerChanged::class, fn (ProducerChanged $e): bool => $e->producerId === $producer->id);
    }

    public function test_renaming_another_dictionary_value_announces_no_producer_event(): void
    {
        Event::fake([ProducerChanged::class]);
        $admin = UserModel::factory()->admin()->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();

        $this->send(Actor::user($admin->id), $colour, 'Inna nazwa');

        Event::assertNotDispatched(ProducerChanged::class);
    }

    public function test_a_producer_renamed_to_its_own_name_announces_nothing(): void
    {
        Event::fake([ProducerChanged::class]);
        $admin = UserModel::factory()->admin()->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Pilkington')->create();

        $this->send(Actor::user($admin->id), $producer, ' Pilkington ');

        Event::assertNotDispatched(ProducerChanged::class);
    }

    public function test_a_name_taken_by_another_value_is_refused_and_announces_nothing(): void
    {
        Event::fake([ProducerChanged::class]);
        $admin = UserModel::factory()->admin()->create();
        DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Pilkington')->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Saint-Gobain')->create();

        try {
            $this->send(Actor::user($admin->id), $producer, 'PILKINGTON');
            $this->fail('A taken name was accepted');
        } catch (DictionaryValueRefused) {
            $this->assertSame('Saint-Gobain', $producer->fresh()->name);
            Event::assertNotDispatched(ProducerChanged::class);
        }
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $value = DictionaryValueModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user($seller->id), $value, 'X');
    }
}
