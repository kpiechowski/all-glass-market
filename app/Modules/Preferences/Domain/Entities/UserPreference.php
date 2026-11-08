<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Domain\Entities;

use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;

/**
 * One user's value of one preference (ADR 0006). The value arrives already normalised by its
 * definition; the entity only guards who owns it and under which key it lives.
 */
final class UserPreference
{
    private function __construct(
        private ?int $id,
        private int $userId,
        private string $key,
        private mixed $value,
    ) {}

    /** The first time a user sets this preference away from its default. */
    public static function choose(int $userId, string $key, mixed $value): self
    {
        if ($userId < 1) {
            throw PreferenceRefused::usersOnly();
        }

        if (trim($key) === '') {
            throw PreferenceRefused::missingKey();
        }

        return new self(null, $userId, $key, $value);
    }

    /** @internal for the mapper */
    public static function reconstitute(int $id, int $userId, string $key, mixed $value): self
    {
        return new self($id, $userId, $key, $value);
    }

    public function change(mixed $value): void
    {
        $this->value = $value;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
