<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleDetails;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;

/**
 * A vehicle (FR-07, FR-08): its own name, a make from the make dictionary, optionally a body, and
 * the plain details. The name is only ever what the admin gave; nothing rewrites it (FR-08). A
 * withdrawn make or body already on the vehicle survives a save, but cannot be newly chosen (FR-24).
 * The facts "name is taken" and the picked dictionary values are gathered by the handler.
 */
final class Vehicle
{
    private function __construct(
        private ?int $id,
        private VehicleName $name,
        private VehicleDetails $details,
        private int $makeId,
        private ?int $bodyId,
    ) {}

    public static function create(
        VehicleName $name,
        VehicleDetails $details,
        DictionaryValue $make,
        ?DictionaryValue $body,
        bool $nameIsTaken,
    ): self {
        $vehicle = new self(null, $name, $details, 0, null);
        $vehicle->change($name, $details, $make, $body, $nameIsTaken);

        return $vehicle;
    }

    /** @internal for the mapper */
    public static function reconstitute(int $id, VehicleName $name, VehicleDetails $details, int $makeId, ?int $bodyId): self
    {
        return new self($id, $name, $details, $makeId, $bodyId);
    }

    /** @param  bool  $nameIsTaken  whether another vehicle already has the name (ignoring case) */
    public function change(
        VehicleName $name,
        VehicleDetails $details,
        DictionaryValue $make,
        ?DictionaryValue $body,
        bool $nameIsTaken,
    ): bool {
        if ($nameIsTaken) {
            throw VehicleRefused::nameIsTaken();
        }

        $this->ensureMakeCanBePicked($make);
        $this->ensureBodyCanBePicked($body);

        $changed = $this->name != $name
            || $this->details != $details
            || $this->makeId !== (int) $make->id()
            || $this->bodyId !== $body?->id();

        $this->name = $name;
        $this->details = $details;
        $this->makeId = (int) $make->id();
        $this->bodyId = $body?->id();

        return $changed;
    }

    private function ensureMakeCanBePicked(DictionaryValue $make): void
    {
        if ($make->dictionary() !== Dictionary::VehicleMake) {
            throw VehicleRefused::makeIsNotAMake();
        }

        if ($make->isWithdrawn() && $make->id() !== $this->makeId) {
            throw VehicleRefused::makeIsWithdrawn();
        }
    }

    private function ensureBodyCanBePicked(?DictionaryValue $body): void
    {
        if ($body === null) {
            return;
        }

        if ($body->dictionary() !== Dictionary::VehicleBody) {
            throw VehicleRefused::bodyIsNotABody();
        }

        if ($body->isWithdrawn() && $body->id() !== $this->bodyId) {
            throw VehicleRefused::bodyIsWithdrawn();
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): VehicleName
    {
        return $this->name;
    }

    public function details(): VehicleDetails
    {
        return $this->details;
    }

    public function makeId(): int
    {
        return $this->makeId;
    }

    public function bodyId(): ?int
    {
        return $this->bodyId;
    }
}
