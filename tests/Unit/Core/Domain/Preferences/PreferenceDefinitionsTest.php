<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Domain\Preferences;

use App\Core\Contracts\PreferenceDefinition;
use App\Core\Domain\Preferences\PreferenceDefinitions;
use LogicException;
use PHPUnit\Framework\TestCase;

class PreferenceDefinitionsTest extends TestCase
{
    private function definition(string $key): PreferenceDefinition
    {
        return new class($key) implements PreferenceDefinition
        {
            public function __construct(private string $key) {}

            public function key(): string
            {
                return $this->key;
            }

            public function default(): mixed
            {
                return [];
            }

            public function normalise(mixed $value): mixed
            {
                return $value;
            }
        };
    }

    public function test_it_finds_a_registered_definition_by_its_key(): void
    {
        $definitions = new PreferenceDefinitions;
        $definition = $this->definition('quick_access');
        $definitions->register($definition);

        $this->assertSame($definition, $definitions->find('quick_access'));
    }

    public function test_an_unknown_key_is_not_found(): void
    {
        $definitions = new PreferenceDefinitions;
        $definitions->register($this->definition('quick_access'));

        $this->assertNull($definitions->find('table_columns'));
    }

    public function test_a_key_cannot_be_registered_twice(): void
    {
        $definitions = new PreferenceDefinitions;
        $definitions->register($this->definition('quick_access'));

        $this->expectException(LogicException::class);

        $definitions->register($this->definition('quick_access'));
    }
}
