<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use Tests\Architecture\TestPresenceScanner;

class ProjectTestPresenceTest extends TestCase
{
    public function test_every_handler_entity_policy_mapper_queries_and_listener_has_a_test(): void
    {
        $scanner = new TestPresenceScanner(dirname(__DIR__, 3));
        $untested = array_values(array_diff($scanner->missingTests(), $this->allowList()));

        $this->assertSame([], $untested, "Classes without a mirrored {Class}Test.php under tests/Unit or tests/Feature:\n".implode("\n", $untested));
    }

    public function test_the_allow_list_only_holds_classes_that_still_lack_a_test(): void
    {
        $scanner = new TestPresenceScanner(dirname(__DIR__, 3));
        $stale = array_values(array_diff($this->allowList(), $scanner->missingTests()));

        $this->assertSame([], $stale, "Remove from tests/Architecture/baselines/test-presence.php:\n".implode("\n", $stale));
    }

    /**
     * @return list<string>
     */
    private function allowList(): array
    {
        return require dirname(__DIR__, 2).'/Architecture/baselines/test-presence.php';
    }
}
