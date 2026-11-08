<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Infrastructure\Persistence\Mappers;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Domain\Entities\Change;
use App\Modules\Audit\Infrastructure\Persistence\Mappers\ChangeMapper;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entry_round_trips_through_the_row(): void
    {
        $mapper = new ChangeMapper;
        $change = Change::record(
            'App\\Thing', 5, ChangeKind::Changed, null,
            ['name' => ['old' => 'a', 'new' => 'b'], 'price' => ['old' => null, 'new' => 10]],
            ['tags' => ['added' => [1, 2], 'removed' => [3]]],
            null, 'Harmonogram', new DateTimeImmutable('2026-11-05 10:00:00'),
        );

        $model = ChangeModel::factory()->bySystem()->create($mapper->toRow($change));
        $back = $mapper->toEntity($model->fresh());

        $this->assertSame($change->recordType(), $back->recordType());
        $this->assertSame($change->recordId(), $back->recordId());
        $this->assertSame($change->kind(), $back->kind());
        $this->assertSame($change->changes(), $back->changes());
        $this->assertSame($change->relationChanges(), $back->relationChanges());
        $this->assertNull($back->actorId());
        $this->assertSame('Harmonogram', $back->actorReason());
        $this->assertEquals($change->occurredAt(), $back->occurredAt());
    }
}
