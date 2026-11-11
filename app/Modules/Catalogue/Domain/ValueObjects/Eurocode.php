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
        $value = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($typed)) ?? '';

        if ($value === '') {
            throw GlassModelRefused::eurocodeIsBlank();
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw GlassModelRefused::eurocodeIsTooLong(self::MAX_LENGTH);
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
