<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

final class Widget
{
    private function __construct(private ?int $id, private string $name) {}

    public static function create(string $name): self
    {
        return new self(null, $name);
    }

    public static function reconstitute(int $id, string $name): self
    {
        return new self($id, $name);
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }
}
