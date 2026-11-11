<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Exceptions;

use App\Core\Exceptions\DomainException;
use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\ValueObjects\EurocodeHolder;

final class GlassModelRefused extends DomainException
{
    public static function eurocodeIsBlank(): self
    {
        return new self('catalogue::exceptions.glass_model.eurocode_is_blank');
    }

    public static function eurocodeIsTooLong(int $max): self
    {
        return new self('catalogue::exceptions.glass_model.eurocode_is_too_long', ['max' => $max]);
    }

    /** RULE-01: the refusal names the model that holds the code. */
    public static function eurocodeIsTaken(string $code, EurocodeHolder $holder): self
    {
        return new self('catalogue::exceptions.glass_model.eurocode_is_taken', [
            'code' => $code,
            'holder' => $holder->mainEurocode,
        ]);
    }

    public static function eurocodeIsNotHeld(string $code): self
    {
        return new self('catalogue::exceptions.glass_model.eurocode_is_not_held', ['code' => $code]);
    }

    public static function eurocodeAlreadyHeld(string $code): self
    {
        return new self('catalogue::exceptions.glass_model.eurocode_already_held', ['code' => $code]);
    }

    /** RULE-02: a model keeps at least one Eurocode. */
    public static function lastEurocode(string $code): self
    {
        return new self('catalogue::exceptions.glass_model.last_eurocode', ['code' => $code]);
    }

    /** RULE-02: the main Eurocode stays until another one is main. */
    public static function mainEurocode(string $code): self
    {
        return new self('catalogue::exceptions.glass_model.main_eurocode', ['code' => $code]);
    }

    /** FR-10: only a vehicle the model fits can be removed from its fitment. */
    public static function vehicleIsNotFitted(): self
    {
        return new self('catalogue::exceptions.glass_model.vehicle_is_not_fitted');
    }

    public static function cannotMoveToItself(): self
    {
        return new self('catalogue::exceptions.glass_model.cannot_move_to_itself');
    }

    public static function oemCodeIsTooLong(int $max): self
    {
        return new self('catalogue::exceptions.glass_model.oem_code_is_too_long', ['max' => $max]);
    }

    public static function positionIsRequired(): self
    {
        return new self('catalogue::exceptions.glass_model.position_is_required');
    }

    public static function valueDoesNotExist(): self
    {
        return new self('catalogue::exceptions.glass_model.value_does_not_exist');
    }

    public static function valueIsOfAnotherDictionary(): self
    {
        return new self('catalogue::exceptions.glass_model.value_is_of_another_dictionary');
    }

    public static function valueIsWithdrawn(string $name): self
    {
        return new self('catalogue::exceptions.glass_model.value_is_withdrawn', ['name' => $name]);
    }

    public static function alreadyWithdrawn(): self
    {
        return new self('catalogue::exceptions.glass_model.already_withdrawn');
    }

    public static function notWithdrawn(): self
    {
        return new self('catalogue::exceptions.glass_model.not_withdrawn');
    }

    /** @param  non-empty-list<DictionaryUse>  $uses */
    public static function inUse(array $uses): self
    {
        $places = implode(', ', array_map(
            fn (DictionaryUse $use): string => __($use->place).' ('.$use->count.')',
            $uses,
        ));

        return new self('catalogue::exceptions.glass_model.in_use', ['places' => $places]);
    }
}
