<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;

/** RULE-03: uppercase letters and digits only (everything else typed is removed), 1 to 17 characters. */
final readonly class Eurocode
{
    public const int MAX_LENGTH = 17;

    private function __construct(public string $value) {}

    public static function from(string $typed): self
    {
        $value = self::normalise($typed);

        if ($value === '') {
            throw GlassModelRefused::eurocodeIsBlank();
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw GlassModelRefused::eurocodeIsTooLong(self::MAX_LENGTH);
        }

        return new self($value);
    }

    /** RULE-03 without the length checks: uppercase, letters and digits only. For suggestions the user then confirms. */
    public static function normalise(string $typed): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($typed)) ?? '';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
