<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentOfferRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OfferRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(OfferRepository::class);
    }

    /** Repository writes record a change, which needs the Actor of a running command. */
    private function asCommand(callable $callback): mixed
    {
        return app(ActorStack::class)->run(Actor::user(1), $callback);
    }

    private function content(?string $hint = 'OEM 123'): OfferContent
    {
        return OfferContent::from(GlassReference::from(null, $hint), null, null, OfferCondition::New, null, null, null, null);
    }

    public function test_an_added_entity_can_be_fetched_again(): void
    {
        $ownerId = OfferModelFactory::new()->create()->owner_id;
        $offer = Offer::draft(OfferNumber::from('AG00000042'), $ownerId, $this->content(), true, true);

        $id = $this->asCommand(fn () => $this->repository->add($offer));

        $fetched = $this->repository->get($id);
        $this->assertSame('AG00000042', $fetched->number()->value);
        $this->assertSame($ownerId, $fetched->ownerId());
        $this->assertSame('OEM 123', $fetched->content()->glass->identificationHint);
        $this->assertSame($id, $this->repository->find($id)?->id());
    }

    public function test_get_throws_and_find_returns_null_for_a_missing_id(): void
    {
        $this->assertNull($this->repository->find(999));

        $this->expectException(ModelNotFoundException::class);

        $this->repository->get(999);
    }

    public function test_a_saved_entity_keeps_its_changes_and_its_number_and_owner(): void
    {
        $model = OfferModelFactory::new()->create(['number' => 'AG00000001']);
        $entity = $this->repository->get($model->id);

        $entity->edit($this->content('NOWY'), $entity->photos(), $entity->ownerId(), false, true, true);
        $this->asCommand(fn () => $this->repository->save($entity));

        $fresh = OfferModel::findOrFail($model->id);
        $this->assertSame('NOWY', $fresh->identification_hint);
        $this->assertSame('AG00000001', $fresh->number);
        $this->assertSame($model->owner_id, $fresh->owner_id);
    }

    public function test_number_is_taken_matches_only_that_number(): void
    {
        OfferModelFactory::new()->create(['number' => 'AG00000001']);

        $this->assertTrue($this->repository->numberIsTaken('AG00000001'));
        $this->assertFalse($this->repository->numberIsTaken('AG00000002'));
    }

    public function test_the_database_refuses_a_duplicate_number(): void
    {
        OfferModelFactory::new()->create(['number' => 'AG00000001']);

        $this->expectException(QueryException::class);

        OfferModelFactory::new()->create(['number' => 'AG00000001']);
    }
}
