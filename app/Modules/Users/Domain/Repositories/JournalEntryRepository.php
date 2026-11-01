<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Users\Domain\Entities\JournalEntry;

/**
 * @extends Repository<JournalEntry>
 */
interface JournalEntryRepository extends Repository {}
