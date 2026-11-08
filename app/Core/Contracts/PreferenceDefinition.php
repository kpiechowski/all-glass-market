<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Exceptions\DomainException;

/**
 * One per-user preference, defined in code (ADR 0006). The Preferences module stores the value
 * under `key()`; the definition owns its shape. A module registers its definitions in its
 * provider's boot():
 *
 *     $this->app->make(PreferenceDefinitions::class)->register($this->app->make(TableDensityPreference::class));
 *
 * A user without a stored value gets `default()`. A save that names a key nobody registered is
 * refused before any definition runs.
 */
interface PreferenceDefinition
{
    /** Stable, stored in every row: lowercase snake_case, never rename it. */
    public function key(): string;

    /** What a user without a stored value gets. JSON-serialisable. */
    public function default(): mixed;

    /**
     * Validates a value about to be saved and returns it in its stored shape.
     *
     * @throws DomainException with a translated message when the value is refused
     */
    public function normalise(mixed $value): mixed;
}
