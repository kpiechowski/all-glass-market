<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\CommandBus;
use RuntimeException;

/** Creates a widget, edits it and syncs its tags in one command; optionally nests another and/or fails. */
final class RecordWidgetHandler
{
    #[CommandHandler]
    public function handle(RecordWidgetCommand $command, CommandBus $bus): void
    {
        $model = ReadOnlyModel::writing(fn () => RecordedWidgetModel::create(['name' => 'gear']));
        ReadOnlyModel::writing(fn () => $model->update(['name' => 'cog']));
        (new RecordedWidgetRepository)->syncTags($model->id, [1, 2]);

        if ($command->nested) {
            $bus->send(new RecordWidgetCommand(Actor::system('inner job')));
        }

        if ($command->fail) {
            throw new RuntimeException('refused after writing');
        }
    }
}
