<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;

/**
 * One entry of one of the six dictionaries (FR-12, FR-13): a name and the withdrawn flag.
 * The facts "name is taken" and "where is it used" are gathered by the handler and passed in.
 */
final class DictionaryValue
{
    private function __construct(
        private ?int $id,
        private readonly Dictionary $dictionary,
        private DictionaryValueName $name,
        private bool $withdrawn,
    ) {}

    public static function create(Dictionary $dictionary, DictionaryValueName $name, bool $nameIsTaken): self
    {
        if ($nameIsTaken) {
            throw DictionaryValueRefused::nameIsTaken();
        }

        return new self(null, $dictionary, $name, false);
    }

    /** @internal for the mapper */
    public static function reconstitute(int $id, Dictionary $dictionary, DictionaryValueName $name, bool $withdrawn): self
    {
        return new self($id, $dictionary, $name, $withdrawn);
    }

    /**
     * @param  bool  $nameIsTaken  whether another value of this dictionary already has the name (ignoring case)
     * @return bool whether the name changed
     */
    public function rename(DictionaryValueName $name, bool $nameIsTaken): bool
    {
        if ($nameIsTaken) {
            throw DictionaryValueRefused::nameIsTaken();
        }

        $changed = $name->value !== $this->name->value;
        $this->name = $name;

        return $changed;
    }

    public function withdraw(): void
    {
        if ($this->withdrawn) {
            throw DictionaryValueRefused::alreadyWithdrawn();
        }

        $this->withdrawn = true;
    }

    public function restore(): void
    {
        if (! $this->withdrawn) {
            throw DictionaryValueRefused::notWithdrawn();
        }

        $this->withdrawn = false;
    }

    /**
     * FR-24: only a value nothing uses may be deleted.
     *
     * @param  list<DictionaryUse>  $uses
     */
    public function ensureCanBeDeleted(array $uses): void
    {
        if ($uses !== []) {
            throw DictionaryValueRefused::inUse($uses);
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function dictionary(): Dictionary
    {
        return $this->dictionary;
    }

    public function name(): DictionaryValueName
    {
        return $this->name;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn;
    }
}
