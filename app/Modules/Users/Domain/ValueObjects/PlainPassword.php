<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\UserRefused;

/** RULE-03: a password has at least 8 characters. Confirmation is a UI concern. */
final readonly class PlainPassword
{
    public const int MIN_LENGTH = 8;

    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        if (mb_strlen($value) < self::MIN_LENGTH) {
            throw UserRefused::passwordTooShort();
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
