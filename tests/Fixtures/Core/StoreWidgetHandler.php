<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use Ecotone\Modelling\Attribute\CommandHandler;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class StoreWidgetHandler
{
    #[CommandHandler]
    public function handle(StoreWidgetCommand $command): void
    {
        DB::table('widgets')->insert(['name' => $command->name]);

        if ($command->fail) {
            throw new RuntimeException('refused after writing');
        }
    }
}
