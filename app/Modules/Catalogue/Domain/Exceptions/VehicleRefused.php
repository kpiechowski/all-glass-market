<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class VehicleRefused extends DomainException
{
    public static function nameIsBlank(): self
    {
        return new self('catalogue::exceptions.vehicle.name_is_blank');
    }

    public static function nameIsTooLong(int $max): self
    {
        return new self('catalogue::exceptions.vehicle.name_is_too_long', ['max' => $max]);
    }

    public static function nameIsTaken(): self
    {
        return new self('catalogue::exceptions.vehicle.name_is_taken');
    }

    public static function makeIsNotAMake(): self
    {
        return new self('catalogue::exceptions.vehicle.make_is_not_a_make');
    }

    public static function bodyIsNotABody(): self
    {
        return new self('catalogue::exceptions.vehicle.body_is_not_a_body');
    }

    public static function makeIsWithdrawn(): self
    {
        return new self('catalogue::exceptions.vehicle.make_is_withdrawn');
    }

    public static function bodyIsWithdrawn(): self
    {
        return new self('catalogue::exceptions.vehicle.body_is_withdrawn');
    }

    public static function modelIsTooLong(int $max): self
    {
        return new self('catalogue::exceptions.vehicle.model_is_too_long', ['max' => $max]);
    }

    public static function generationIsTooLong(int $max): self
    {
        return new self('catalogue::exceptions.vehicle.generation_is_too_long', ['max' => $max]);
    }

    public static function yearIsInvalid(): self
    {
        return new self('catalogue::exceptions.vehicle.year_is_invalid');
    }

    public static function yearsAreOutOfOrder(): self
    {
        return new self('catalogue::exceptions.vehicle.years_are_out_of_order');
    }

    public static function argicCodeIsInvalid(): self
    {
        return new self('catalogue::exceptions.vehicle.argic_code_is_invalid');
    }
}
