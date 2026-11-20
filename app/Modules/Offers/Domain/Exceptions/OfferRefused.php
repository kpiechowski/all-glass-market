<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class OfferRefused extends DomainException
{
    public static function glassModelAndHintTogether(): self
    {
        return new self('offers::exceptions.glass_model_and_hint_together');
    }

    public static function hintIsTooLong(int $max): self
    {
        return new self('offers::exceptions.hint_is_too_long', ['max' => $max]);
    }

    public static function ratingIsInvalid(): self
    {
        return new self('offers::exceptions.rating_is_invalid');
    }

    public static function ratingNotForNew(): self
    {
        return new self('offers::exceptions.rating_not_for_new');
    }

    public static function ratingNeedsCondition(): self
    {
        return new self('offers::exceptions.rating_needs_condition');
    }

    public static function priceIsInvalid(): self
    {
        return new self('offers::exceptions.price_is_invalid');
    }

    public static function yearIsInvalid(): self
    {
        return new self('offers::exceptions.year_is_invalid');
    }

    public static function numberIsInvalid(): self
    {
        return new self('offers::exceptions.number_is_invalid');
    }

    public static function conditionIsUnknown(): self
    {
        return new self('offers::exceptions.condition_is_unknown');
    }

    public static function glassModelCannotBePicked(): self
    {
        return new self('offers::exceptions.glass_model_cannot_be_picked');
    }

    public static function producerCannotBePicked(): self
    {
        return new self('offers::exceptions.producer_cannot_be_picked');
    }

    public static function notYours(): self
    {
        return new self('offers::exceptions.not_yours');
    }
}
