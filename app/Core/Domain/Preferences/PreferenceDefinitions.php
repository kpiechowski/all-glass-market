<?php

declare(strict_types=1);

namespace App\Core\Domain\Preferences;

use App\Core\Contracts\PreferenceDefinition;
use LogicException;

/**
 * Every preference definition the installed modules registered (ADR 0006). A singleton bound by
 * the Core provider; registration lives in memory, so a module that is not installed registers
 * nothing. The Preferences module resolves a definition here before it saves or reads a value.
 */
final class PreferenceDefinitions
{
    /** @var array<string, PreferenceDefinition> */
    private array $definitions = [];

    public function register(PreferenceDefinition $definition): void
    {
        $key = $definition->key();

        if (isset($this->definitions[$key])) {
            throw new LogicException("The preference [{$key}] is already registered.");
        }

        $this->definitions[$key] = $definition;
    }

    public function find(string $key): ?PreferenceDefinition
    {
        return $this->definitions[$key] ?? null;
    }
}
