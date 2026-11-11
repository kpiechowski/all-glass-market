<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;

/** RULE-08: any characters, trimmed, unique per dictionary regardless of letter case. */
final readonly class DictionaryValueName
{
    public const int MAX_LENGTH = 255;

    private function __construct(public string $value) {}

    public static function from(string $name): self
    {
        $name = trim($name);

        if ($name === '') {
            throw DictionaryValueRefused::nameIsBlank();
        }

        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw DictionaryValueRefused::nameIsTooLong(self::MAX_LENGTH);
        }

        return new self($name);
    }

    /** The form two names are compared by. */
    public function key(): string
    {
        return mb_strtolower($this->value);
    }
}
