<?php

declare(strict_types=1);

namespace App\Core\UserInterface;

use App\Core\Exceptions\DomainException;
use Ecotone\Modelling\CommandBus;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

/**
 * The only way a Filament page or action writes: put a command on the bus.
 * A domain refusal becomes a danger notification and halts the action, so a
 * form stays open with its data.
 */
final class CommandSender
{
    public static function send(object $command): mixed
    {
        try {
            return app(CommandBus::class)->send($command);
        } catch (DomainException $exception) {
            Notification::make()
                ->danger()
                ->title(__($exception->translationKey, $exception->parameters))
                ->send();

            throw new Halt;
        }
    }
}
