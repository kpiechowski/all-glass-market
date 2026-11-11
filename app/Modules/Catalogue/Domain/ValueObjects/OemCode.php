<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;

/** RULE-04: stored as typed, uppercase and trimmed; two codes are the same when `key()` matches. */
final readonly class OemCode
{
    public const int MAX_LENGTH = 255;

    private function __construct(public string $value) {}

    /** Null for an empty entry: it carries no code. */
    public static function tryFrom(string $typed): ?self
    {
        $value = mb_strtoupper(trim($typed));

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw GlassModelRefused::oemCodeIsTooLong(self::MAX_LENGTH);
        }

        return new self($value);
    }

    /** The form two codes are compared by: spaces, dashes and dots ignored. */
    public function key(): string
    {
        return self::keyOf($this->value);
    }

    /** The compared form of any typed text, also of a search fragment. */
    public static function keyOf(string $typed): string
    {
        return str_replace([' ', '-', '.'], '', mb_strtoupper(trim($typed)));
    }
}
