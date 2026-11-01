<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Users\Domain\Entities\JournalEntry;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use App\Modules\Users\Infrastructure\Persistence\Mappers\JournalEntryMapper;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;

/** @extends EloquentRepository<JournalEntry> */
final class EloquentJournalEntryRepository extends EloquentRepository implements JournalEntryRepository
{
    public function __construct(JournalEntryMapper $mapper)
    {
        parent::__construct(JournalEntryModel::class, $mapper);
    }
}
