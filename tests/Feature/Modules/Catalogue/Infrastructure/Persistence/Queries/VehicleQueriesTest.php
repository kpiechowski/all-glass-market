<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\VehicleQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_make_and_body(): void
    {
        $body = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->create();
        $a = VehicleModelFactory::new()->withBody($body->id)->create();
        $b = VehicleModelFactory::new()->create();
        $queries = new VehicleQueries;

        $this->assertSame([$a->id], $queries->ofMake($queries->listing(), $a->make_id)->pluck('id')->all());
        $this->assertSame([$a->id], $queries->ofBody($queries->listing(), $body->id)->pluck('id')->all());
        $this->assertNotContains($b->id, $queries->ofBody($queries->listing(), $body->id)->pluck('id')->all());
    }

    public function test_offered_makes_leave_out_withdrawn_ones_except_the_current_one(): void
    {
        $active = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->withdrawn()->create();
        $otherWithdrawn = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->withdrawn()->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $queries = new VehicleQueries;

        $this->assertEqualsCanonicalizing([$active->id], $queries->offeredMakes()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$active->id, $withdrawn->id], $queries->offeredMakes($withdrawn->id)->pluck('id')->all());
        $this->assertNotContains($otherWithdrawn->id, $queries->offeredMakes($withdrawn->id)->pluck('id')->all());
        $this->assertNotContains($colour->id, $queries->offeredMakes()->pluck('id')->all());
    }

    public function test_offered_bodies_follow_the_same_rule_and_the_filters_list_every_value(): void
    {
        $active = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->create();
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->withdrawn()->create();
        $queries = new VehicleQueries;

        $this->assertSame([$active->id], $queries->offeredBodies()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$active->id, $withdrawn->id], $queries->allBodies()->pluck('id')->all());
    }
}
