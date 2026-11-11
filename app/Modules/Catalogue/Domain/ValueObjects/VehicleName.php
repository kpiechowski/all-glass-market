<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;

/** FR-08, RULE-09: required, any characters, trimmed, unique regardless of letter case. */
final readonly class VehicleName
{
    public const int MAX_LENGTH = 255;

    private function __construct(public string $value) {}

    public static function from(string $name): self
    {
        $name = trim($name);

        if ($name === '') {
            throw VehicleRefused::nameIsBlank();
        }

        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw VehicleRefused::nameIsTooLong(self::MAX_LENGTH);
        }

        return new self($name);
    }

    /** The form two names are compared by. */
    public function key(): string
    {
        return mb_strtolower($this->value);
    }
}
