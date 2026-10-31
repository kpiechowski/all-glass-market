<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Infrastructure\Bus;

use App\Core\Infrastructure\Bus\WrapCommandInTransaction;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Modelling\CommandBus;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Fixtures\Core\CreatesWidgetsTable;
use Tests\Fixtures\Core\StoreWidgetCommand;
use Tests\Fixtures\Core\StoreWidgetHandler;
use Tests\TestCase;

class WrapCommandInTransactionTest extends TestCase
{
    use CreatesWidgetsTable;

    private CommandBus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createWidgetsTable();

        $this->bus = EcotoneLite::bootstrapFlowTesting(
            [StoreWidgetHandler::class, WrapCommandInTransaction::class],
            [new StoreWidgetHandler, new WrapCommandInTransaction],
        )->getGateway(CommandBus::class);
    }

    public function test_a_command_that_succeeds_is_committed(): void
    {
        $this->bus->send(new StoreWidgetCommand('gear', fail: false));

        $this->assertSame(1, DB::table('widgets')->count());
    }

    public function test_a_command_that_throws_rolls_back_everything_it_wrote(): void
    {
        try {
            $this->bus->send(new StoreWidgetCommand('gear', fail: true));
            $this->fail('The handler exception was swallowed');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, DB::table('widgets')->count());
    }
}
