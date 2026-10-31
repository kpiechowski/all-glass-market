<?php

declare(strict_types=1);

namespace App\Core\Domain;

use InvalidArgumentException;

/**
 * Who is behind a command: a person or the system. Commands carry this
 * instead of a bare user id so that a forgotten actor can never run as system.
 */
final readonly class Actor
{
    private function __construct(
        public ?int $id,
        public ?string $reason,
    ) {}

    public static function user(int $id): self
    {
        if ($id < 1) {
            throw new InvalidArgumentException('A user actor needs a real user id.');
        }

        return new self($id, null);
    }

    public static function system(string $reason): self
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A system actor must name its reason.');
        }

        return new self(null, $reason);
    }

    public function isSystem(): bool
    {
        return $this->id === null;
    }
}
