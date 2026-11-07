<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Preferences\Domain\Entities\UserPreference;

/**
 * @extends Repository<UserPreference>
 */
interface UserPreferenceRepository extends Repository
{
    /** The user's stored value under that key, or null while they keep the default. */
    public function findFor(int $userId, string $key): ?UserPreference;
}
