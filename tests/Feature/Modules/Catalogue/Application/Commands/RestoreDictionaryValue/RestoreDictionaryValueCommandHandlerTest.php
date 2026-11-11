<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\RestoreDictionaryValue;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\RestoreDictionaryValue\RestoreDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-13. */
class RestoreDictionaryValueCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, DictionaryValueModel $value): void
    {
        app(CommandBus::class)->send(new RestoreDictionaryValueCommand($actor, $value->id));
    }

    public function test_an_admin_restores_a_withdrawn_value(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $value = DictionaryValueModelFactory::new()->withdrawn()->create();

        $this->send(Actor::user($admin->id), $value);

        $this->assertFalse($value->fresh()->withdrawn);
    }

    public function test_an_active_value_cannot_be_restored(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $value = DictionaryValueModelFactory::new()->create();

        $this->expectException(DictionaryValueRefused::class);

        $this->send(Actor::user($admin->id), $value);
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $value = DictionaryValueModelFactory::new()->withdrawn()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user($seller->id), $value);
    }
}
