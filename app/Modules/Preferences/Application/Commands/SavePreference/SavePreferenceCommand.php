<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Application\Commands\SavePreference;

use App\Core\Domain\Actor;

/**
 * The actor sets one of their own preferences. The value is plain JSON data (scalars and arrays
 * of them); its definition decides whether it is acceptable.
 */
final readonly class SavePreferenceCommand
{
    /**
     * @param  string|int|float|bool|array<array-key, mixed>|null  $value
     */
    public function __construct(
        public Actor $actor,
        public string $key,
        public string|int|float|bool|array|null $value,
    ) {}
}
