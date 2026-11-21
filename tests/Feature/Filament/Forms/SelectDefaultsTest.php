<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Forms;

use Filament\Forms\Components\Select;
use Tests\TestCase;

/** SelectDefaults: every panel select uses the JavaScript dropdown unless it opts back in to the native one. */
class SelectDefaultsTest extends TestCase
{
    public function test_a_select_is_not_native_by_default(): void
    {
        $this->assertFalse(Select::make('field')->isNative());
    }

    public function test_a_select_may_still_opt_in_to_the_native_dropdown(): void
    {
        $this->assertTrue(Select::make('field')->native()->isNative());
    }
}
