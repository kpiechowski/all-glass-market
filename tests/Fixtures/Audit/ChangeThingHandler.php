<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Ecotone\Modelling\Attribute\CommandHandler;
use RuntimeException;

final class ChangeThingHandler
{
    #[CommandHandler]
    public function handle(ChangeThingCommand $command): ?int
    {
        return ReadOnlyModel::writing(function () use ($command): ?int {
            $thing = $command->thingId === null ? null : ThingModel::withTrashed()->findOrFail($command->thingId);

            switch ($command->action) {
                case 'create':
                    $thing = ThingModel::create($command->attributes);

                    if ($command->producerIds !== []) {
                        (new ThingRepository)->syncProducers($thing->id, $command->producerIds);
                    }

                    break;
                case 'update':
                    if (($command->attributes['price'] ?? 0) > 9999) {
                        throw new ThingRefused('Cena rzeczy nie może przekraczać 9999.');
                    }

                    $thing->update($command->attributes);

                    if ($command->producerIds !== []) {
                        (new ThingRepository)->syncProducers($thing->id, $command->producerIds);
                    }

                    break;
                case 'delete':
                    $thing->delete();
                    break;
                case 'restore':
                    $thing->restore();
                    break;
                case 'sync':
                    (new ThingRepository)->syncProducers($thing->id, $command->producerIds);
                    break;
                default:
                    throw new RuntimeException('Unknown action');
            }

            return $thing->id;
        });
    }
}
