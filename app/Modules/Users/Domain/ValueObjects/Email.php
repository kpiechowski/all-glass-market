<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\UserRefused;

/** RULE-01: a well-formed e-mail address, kept in lower case. */
final readonly class Email
{
    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        $value = mb_strtolower(trim($value));

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false || mb_strlen($value) > 255) {
            throw UserRefused::invalidEmail();
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
