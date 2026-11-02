<?php

declare(strict_types=1);

namespace App\Core\UserInterface;

use App\Core\Exceptions\DomainException;
use Ecotone\Modelling\CommandBus;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

/**
 * The only way a Filament page or action writes: put a command on the bus.
 * A domain refusal becomes a danger notification and halts the action, so a
 * form stays open with its data. A refusal named in `$fieldErrors` is shown on
 * those fields instead.
 */
final class CommandSender
{
    /**
     * @param  array<string, list<string>>  $fieldErrors  translation key of a refusal => form fields that show it
     *                                                    instead of a notification, by their path in the form
     *                                                    (for example `data.company_nip`)
     */
    public static function send(object $command, array $fieldErrors = []): mixed
    {
        try {
            return app(CommandBus::class)->send($command);
        } catch (DomainException $exception) {
            if (isset($fieldErrors[$exception->translationKey])) {
                $message = __($exception->translationKey, $exception->parameters);

                throw ValidationException::withMessages(
                    array_fill_keys($fieldErrors[$exception->translationKey], $message),
                );
            }

            Notification::make()
                ->danger()
                ->title(__($exception->translationKey, $exception->parameters))
                ->send();

            throw new Halt;
        }
    }
}
