<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\EurocodeHolder;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;

/**
 * A kind of glass (ADR 0008): its Eurocodes (one main, RULE-02), position, colour, equipment and
 * OEM codes. The Eurocodes are held as a list so that further ones (ticket 04) join without
 * reshaping the entity; here only the main one is entered. Facts gathered by the handler (who holds
 * a Eurocode) are passed in.
 */
final class GlassModel
{
    /**
     * @param  non-empty-list<Eurocode>  $eurocodes  every Eurocode, equal in finding the model
     * @param  list<int>  $equipmentIds
     * @param  list<OemCode>  $oemCodes  each once by RULE-04's comparison
     */
    private function __construct(
        private ?int $id,
        private array $eurocodes,
        private Eurocode $mainEurocode,
        private int $positionId,
        private ?int $colourId,
        private array $equipmentIds,
        private array $oemCodes,
        private bool $withdrawn,
    ) {}

    /**
     * @param  list<int>  $equipmentIds
     * @param  list<OemCode>  $oemCodes
     * @param  EurocodeHolder|null  $holder  the model already holding the Eurocode, if any (RULE-01)
     */
    public static function create(
        Eurocode $mainEurocode,
        ?int $positionId,
        ?int $colourId,
        array $equipmentIds,
        array $oemCodes,
        ?EurocodeHolder $holder,
    ): self {
        if ($holder !== null) {
            throw GlassModelRefused::eurocodeIsTaken($mainEurocode->value, $holder);
        }

        if ($positionId === null) {
            throw GlassModelRefused::positionIsRequired();
        }

        return new self(
            null,
            [$mainEurocode],
            $mainEurocode,
            $positionId,
            $colourId,
            self::distinctIds($equipmentIds),
            self::distinctOemCodes($oemCodes),
            false,
        );
    }

    /**
     * @internal for the mapper
     *
     * @param  non-empty-list<Eurocode>  $eurocodes
     * @param  list<int>  $equipmentIds
     * @param  list<OemCode>  $oemCodes
     */
    public static function reconstitute(
        int $id,
        array $eurocodes,
        Eurocode $mainEurocode,
        int $positionId,
        ?int $colourId,
        array $equipmentIds,
        array $oemCodes,
        bool $withdrawn,
    ): self {
        return new self($id, $eurocodes, $mainEurocode, $positionId, $colourId, $equipmentIds, $oemCodes, $withdrawn);
    }

    /**
     * FR-02: also while withdrawn.
     *
     * @param  list<int>  $equipmentIds
     * @param  list<OemCode>  $oemCodes
     * @return bool whether anything changed
     */
    public function changeParameters(?int $positionId, ?int $colourId, array $equipmentIds, array $oemCodes): bool
    {
        if ($positionId === null) {
            throw GlassModelRefused::positionIsRequired();
        }

        $equipmentIds = self::distinctIds($equipmentIds);
        $oemCodes = self::distinctOemCodes($oemCodes);

        $changed = $positionId !== $this->positionId
            || $colourId !== $this->colourId
            || self::sorted($equipmentIds) !== self::sorted($this->equipmentIds)
            || self::sorted(array_map(fn (OemCode $code): string => $code->value, $oemCodes))
                !== self::sorted(array_map(fn (OemCode $code): string => $code->value, $this->oemCodes));

        $this->positionId = $positionId;
        $this->colourId = $colourId;
        $this->equipmentIds = $equipmentIds;
        $this->oemCodes = $oemCodes;

        return $changed;
    }

    public function withdraw(): void
    {
        if ($this->withdrawn) {
            throw GlassModelRefused::alreadyWithdrawn();
        }

        $this->withdrawn = true;
    }

    public function restore(): void
    {
        if (! $this->withdrawn) {
            throw GlassModelRefused::notWithdrawn();
        }

        $this->withdrawn = false;
    }

    /**
     * FR-24: only a model nothing uses may be deleted.
     *
     * @param  list<DictionaryUse>  $uses
     */
    public function ensureCanBeDeleted(array $uses): void
    {
        if ($uses !== []) {
            throw GlassModelRefused::inUse($uses);
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    /** @return non-empty-list<Eurocode> */
    public function eurocodes(): array
    {
        return $this->eurocodes;
    }

    public function mainEurocode(): Eurocode
    {
        return $this->mainEurocode;
    }

    public function positionId(): int
    {
        return $this->positionId;
    }

    public function colourId(): ?int
    {
        return $this->colourId;
    }

    /** @return list<int> */
    public function equipmentIds(): array
    {
        return $this->equipmentIds;
    }

    /** @return list<OemCode> */
    public function oemCodes(): array
    {
        return $this->oemCodes;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn;
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private static function distinctIds(array $ids): array
    {
        return array_values(array_unique($ids));
    }

    /**
     * RULE-04: the first of two codes the same by comparison stays.
     *
     * @param  list<OemCode>  $codes
     * @return list<OemCode>
     */
    private static function distinctOemCodes(array $codes): array
    {
        $distinct = [];

        foreach ($codes as $code) {
            $distinct[$code->key()] ??= $code;
        }

        return array_values($distinct);
    }

    /**
     * @param  list<int|string>  $values
     * @return list<int|string>
     */
    private static function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
