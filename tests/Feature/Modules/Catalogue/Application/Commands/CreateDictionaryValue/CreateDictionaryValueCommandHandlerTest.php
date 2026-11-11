<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\CreateDictionaryValue;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\CreateDictionaryValue\CreateDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** FR-12, RULE-07, RULE-08. */
class CreateDictionaryValueCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, string $dictionary, string $name): int
    {
        return app(CommandBus::class)->send(new CreateDictionaryValueCommand($actor, $dictionary, $name));
    }

    /** @return list<array{string}> */
    public static function dictionaries(): array
    {
        return array_map(fn (Dictionary $d): array => [$d->value], Dictionary::cases());
    }

    #[DataProvider('dictionaries')]
    public function test_an_admin_creates_an_active_value_in_each_dictionary(string $dictionary): void
    {
        $admin = UserModel::factory()->admin()->create();

        $id = $this->send(Actor::user($admin->id), $dictionary, '  Nowa wartość ');

        $model = DictionaryValueModel::findOrFail($id);
        $this->assertSame('Nowa wartość', $model->name);
        $this->assertSame($dictionary, $model->dictionary->value);
        $this->assertFalse($model->withdrawn);
    }

    public function test_a_super_admin_may_create_too(): void
    {
        $superAdmin = UserModel::factory()->superAdmin()->create();

        $this->assertIsInt($this->send(Actor::user($superAdmin->id), 'colour', 'Zielony'));
    }

    public function test_the_same_name_in_another_letter_case_is_refused_within_a_dictionary(): void
    {
        $admin = UserModel::factory()->admin()->create();
        DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();

        $this->expectException(DictionaryValueRefused::class);

        $this->send(Actor::user($admin->id), 'colour', 'ZIELONY');
    }

    public function test_the_same_name_is_fine_in_another_dictionary(): void
    {
        $admin = UserModel::factory()->admin()->create();
        DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();

        $this->assertIsInt($this->send(Actor::user($admin->id), 'equipment', 'zielony'));
    }

    public function test_a_blank_name_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $this->expectException(DictionaryValueRefused::class);

        $this->send(Actor::user($admin->id), 'colour', '  ');
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user($seller->id), 'colour', 'Zielony');
    }
}
