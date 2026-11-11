<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\WithdrawDictionaryValue;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\WithdrawDictionaryValue\WithdrawDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Events\ProducerWithdrawn;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-13, WF-04, §9 Producer withdrawn. */
class WithdrawDictionaryValueCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, DictionaryValueModel $value): void
    {
        app(CommandBus::class)->send(new WithdrawDictionaryValueCommand($actor, $value->id));
    }

    public function test_withdrawing_a_producer_announces_it(): void
    {
        Event::fake([ProducerWithdrawn::class]);
        $admin = UserModel::factory()->admin()->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();

        $this->send(Actor::user($admin->id), $producer);

        $this->assertTrue($producer->fresh()->withdrawn);
        Event::assertDispatched(ProducerWithdrawn::class, fn (ProducerWithdrawn $e): bool => $e->producerId === $producer->id);
    }

    public function test_withdrawing_another_dictionary_value_announces_nothing(): void
    {
        Event::fake([ProducerWithdrawn::class]);
        $admin = UserModel::factory()->admin()->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();

        $this->send(Actor::user($admin->id), $colour);

        $this->assertTrue($colour->fresh()->withdrawn);
        Event::assertNotDispatched(ProducerWithdrawn::class);
    }

    public function test_a_withdrawn_value_cannot_be_withdrawn_again(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $value = DictionaryValueModelFactory::new()->withdrawn()->create();

        $this->expectException(DictionaryValueRefused::class);

        $this->send(Actor::user($admin->id), $value);
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $value = DictionaryValueModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user($seller->id), $value);
    }
}
