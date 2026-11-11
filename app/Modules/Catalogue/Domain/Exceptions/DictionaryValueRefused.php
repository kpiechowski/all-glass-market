<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Exceptions;

use App\Core\Exceptions\DomainException;
use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;

final class DictionaryValueRefused extends DomainException
{
    public static function nameIsBlank(): self
    {
        return new self('catalogue::exceptions.dictionary_value.name_is_blank');
    }

    public static function nameIsTooLong(int $max): self
    {
        return new self('catalogue::exceptions.dictionary_value.name_is_too_long', ['max' => $max]);
    }

    public static function nameIsTaken(): self
    {
        return new self('catalogue::exceptions.dictionary_value.name_is_taken');
    }

    public static function alreadyWithdrawn(): self
    {
        return new self('catalogue::exceptions.dictionary_value.already_withdrawn');
    }

    public static function notWithdrawn(): self
    {
        return new self('catalogue::exceptions.dictionary_value.not_withdrawn');
    }

    /** @param  non-empty-list<DictionaryUse>  $uses */
    public static function inUse(array $uses): self
    {
        $places = implode(', ', array_map(
            fn (DictionaryUse $use): string => __($use->place).' ('.$use->count.')',
            $uses,
        ));

        return new self('catalogue::exceptions.dictionary_value.in_use', ['places' => $places]);
    }
}
