<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentDictionaryValueRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DictionaryValueRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(DictionaryValueRepository::class);
    }

    private function asCommand(callable $callback): mixed
    {
        return app(ActorStack::class)->run(Actor::user(UserModel::factory()->admin()->create()->id), $callback);
    }

    public function test_an_added_value_can_be_fetched_changed_and_removed(): void
    {
        $id = $this->asCommand(fn () => $this->repository->add(
            DictionaryValue::create(Dictionary::Equipment, DictionaryValueName::from('POLE VIN'), false),
        ));

        $value = $this->repository->get($id);
        $this->assertSame('POLE VIN', $value->name()->value);

        $value->withdraw();
        $this->asCommand(fn () => $this->repository->save($value));
        $this->assertTrue(DictionaryValueModel::findOrFail($id)->withdrawn);

        $this->asCommand(fn () => $this->repository->remove($value));
        $this->assertNull($this->repository->find($id));
    }

    public function test_name_is_taken_ignores_case_within_the_dictionary_only(): void
    {
        DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();

        $this->assertTrue($this->repository->nameIsTaken(Dictionary::Colour, 'ZIELONY'));
        $this->assertFalse($this->repository->nameIsTaken(Dictionary::Producer, 'Zielony'));
        $this->assertFalse($this->repository->nameIsTaken(Dictionary::Colour, 'Niebieski'));
    }

    public function test_name_is_taken_leaves_out_the_value_being_renamed(): void
    {
        $own = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();

        $this->assertFalse($this->repository->nameIsTaken(Dictionary::Colour, 'zielony', $own->id));
        $this->assertTrue($this->repository->nameIsTaken(Dictionary::Colour, 'zielony', $own->id + 1));
    }
}
