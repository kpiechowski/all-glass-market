<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
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

    public function test_the_ids_of_an_owner_include_every_status_and_the_trash_and_exclude_other_owners(): void
    {
        $owner = UserModel::factory()->create();
        $first = OfferModelFactory::new()->ownedBy($owner->id)->create();
        $trashed = OfferModelFactory::new()->ownedBy($owner->id)->status(OfferStatus::Active)->inTrash()->create();
        OfferModelFactory::new()->create();

        $this->assertSame([$first->id, $trashed->id], $this->repository->idsOfOwner($owner->id));
    }

    public function test_an_offer_is_a_candidate_while_active_or_reserved_outside_the_trash(): void
    {
        $active = OfferModelFactory::new()->status(OfferStatus::Active)->create();
        $reserved = OfferModelFactory::new()->status(OfferStatus::Reserved)->create();
        $pending = OfferModelFactory::new()->pending()->create();
        $trashed = OfferModelFactory::new()->status(OfferStatus::Active)->inTrash()->create();

        $this->assertTrue($this->repository->isCandidate($active->id));
        $this->assertTrue($this->repository->isCandidate($reserved->id));
        $this->assertFalse($this->repository->isCandidate($pending->id));
        $this->assertFalse($this->repository->isCandidate($trashed->id));
        $this->assertFalse($this->repository->isCandidate(999999));
    }

    public function test_the_candidates_of_a_glass_model_exclude_other_models_other_statuses_and_the_trash(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $other = GlassModelModelFactory::new()->create();
        $active = OfferModelFactory::new()->status(OfferStatus::Active)->withGlassModel($model->id)->create();
        $reserved = OfferModelFactory::new()->status(OfferStatus::Reserved)->withGlassModel($model->id)->create();
        OfferModelFactory::new()->pending()->withGlassModel($model->id)->create();
        OfferModelFactory::new()->status(OfferStatus::Active)->withGlassModel($model->id)->inTrash()->create();
        OfferModelFactory::new()->status(OfferStatus::Active)->withGlassModel($other->id)->create();

        $this->assertSame([$active->id, $reserved->id], $this->repository->candidateIdsOfGlassModel($model->id));
    }

    public function test_the_candidates_of_a_producer_exclude_other_producers_other_statuses_and_the_trash(): void
    {
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        $other = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        $active = OfferModelFactory::new()->status(OfferStatus::Active)->create(['producer_id' => $producer->id]);
        $reserved = OfferModelFactory::new()->status(OfferStatus::Reserved)->create(['producer_id' => $producer->id]);
        OfferModelFactory::new()->pending()->create(['producer_id' => $producer->id]);
        OfferModelFactory::new()->status(OfferStatus::Active)->inTrash()->create(['producer_id' => $producer->id]);
        OfferModelFactory::new()->status(OfferStatus::Active)->create(['producer_id' => $other->id]);

        $this->assertSame([$active->id, $reserved->id], $this->repository->candidateIdsOfProducer($producer->id));
    }

    public function test_priced_offers_leave_out_offers_without_a_price(): void
    {
        $priced = OfferModelFactory::new()->priced(12000)->create();
        OfferModelFactory::new()->create();

        $this->assertSame([['id' => $priced->id, 'ownerId' => $priced->owner_id, 'grosze' => 12000]], $this->repository->pricedOffers());
    }

    public function test_priced_offers_of_an_owner_leave_out_other_owners_and_unpriced_offers(): void
    {
        $owner = UserModel::factory()->create();
        $mine = OfferModelFactory::new()->ownedBy($owner->id)->priced(9900)->create();
        OfferModelFactory::new()->ownedBy($owner->id)->create();
        OfferModelFactory::new()->priced(5000)->create();

        $this->assertSame([['id' => $mine->id, 'grosze' => 9900]], $this->repository->pricedOffersOfOwner($owner->id));
    }
}
