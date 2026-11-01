<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Persistence\Mappers;

use App\Modules\Users\Domain\Entities\JournalEntry;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Mappers\JournalEntryMapper;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use Tests\TestCase;

class JournalEntryMapperTest extends TestCase
{
    public function test_it_round_trips_an_entity_through_a_model(): void
    {
        $mapper = new JournalEntryMapper;
        $entry = JournalEntry::reconstitute(4, 9, 'offer', JournalSeverity::Rejection, 'Odrzucona', "a\nb", 33, true);

        $model = new JournalEntryModel;
        $model->forceFill($mapper->toRow($entry))->setAttribute('id', 4);
        $model->syncOriginal();

        $this->assertEquals($entry, $mapper->toEntity($model));
    }

    public function test_it_round_trips_an_entry_without_a_subject(): void
    {
        $mapper = new JournalEntryMapper;
        $entry = JournalEntry::reconstitute(5, 9, 'users', JournalSeverity::Info, 'T', '', null, false);

        $model = new JournalEntryModel;
        $model->forceFill($mapper->toRow($entry))->setAttribute('id', 5);
        $model->syncOriginal();

        $this->assertEquals($entry, $mapper->toEntity($model));
    }
}
