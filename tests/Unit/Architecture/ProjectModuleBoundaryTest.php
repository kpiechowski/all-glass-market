<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use Tests\Architecture\ModuleBoundaryScanner;

class ProjectModuleBoundaryTest extends TestCase
{
    public function test_modules_import_only_the_public_surface_of_other_modules(): void
    {
        $violations = array_values(array_diff($this->violations(), $this->allowList()));

        $this->assertSame([], $violations, "Imports of another module's private classes (allowed: ModuleApi, Domain/Events, Domain/Contracts, Domain/Enums, Persistence/Models):\n".implode("\n", $violations));
    }

    public function test_the_allow_list_only_holds_violations_that_still_exist(): void
    {
        $stale = array_values(array_diff($this->allowList(), $this->violations()));

        $this->assertSame([], $stale, "Remove from tests/Architecture/baselines/module-boundaries.php:\n".implode("\n", $stale));
    }

    /**
     * @return list<string>
     */
    private function violations(): array
    {
        return (new ModuleBoundaryScanner(dirname(__DIR__, 3)))->violations();
    }

    /**
     * @return list<string>
     */
    private function allowList(): array
    {
        return require dirname(__DIR__, 2).'/Architecture/baselines/module-boundaries.php';
    }
}
