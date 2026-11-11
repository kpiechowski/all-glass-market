<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\DeleteDictionaryValue;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\DeleteDictionaryValue\DeleteDictionaryValueCommand;
use App\Modules\Catalogue\Application\ModuleApi\DictionaryValueUsages;
use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\DictionaryValueUsage;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-24, WF-04: delete only while unused, through the usage seam. */
class DeleteDictionaryValueCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, DictionaryValueModel $value): void
    {
        app(CommandBus::class)->send(new DeleteDictionaryValueCommand($actor, $value->id));
    }

    public function test_an_unused_value_is_deleted_withdrawn_or_not(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $active = DictionaryValueModelFactory::new()->create();
        $withdrawn = DictionaryValueModelFactory::new()->withdrawn()->create();

        $this->send(Actor::user($admin->id), $active);
        $this->send(Actor::user($admin->id), $withdrawn);

        $this->assertNull(DictionaryValueModel::find($active->id));
        $this->assertNull(DictionaryValueModel::find($withdrawn->id));
    }

    public function test_a_value_in_use_is_refused_naming_where_it_is_used(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        $other = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        app(DictionaryValueUsages::class)->register(new class($producer->id) implements DictionaryValueUsage
        {
            public function __construct(private int $usedId) {}

            public function usesOf(Dictionary $dictionary, int $valueId): array
            {
                return $valueId === $this->usedId ? [new DictionaryUse('catalogue::resources.navigation.group', 3)] : [];
            }
        });

        try {
            $this->send(Actor::user($admin->id), $producer);
            $this->fail('A value in use was deleted');
        } catch (DictionaryValueRefused $refusal) {
            $this->assertSame('catalogue::exceptions.dictionary_value.in_use', $refusal->translationKey);
            $this->assertStringContainsString('Katalog (3)', $refusal->parameters['places']);
            $this->assertNotNull(DictionaryValueModel::find($producer->id));
        }

        $this->send(Actor::user($admin->id), $other);
        $this->assertNull(DictionaryValueModel::find($other->id));
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $value = DictionaryValueModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user($seller->id), $value);
    }
}
