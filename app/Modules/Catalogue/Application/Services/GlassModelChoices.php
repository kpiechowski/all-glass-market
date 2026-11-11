<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Services;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;

/**
 * Shared by the create and update handlers: the position, colour and equipment chosen must exist in
 * their dictionary and not be withdrawn, unless the model already holds the value (FR-02, FR-24:
 * a withdrawn value stays on the model but cannot be newly chosen).
 */
final class GlassModelChoices
{
    public function __construct(private DictionaryValueRepository $values) {}

    /**
     * @param  list<int>  $equipmentIds
     * @param  list<int>  $alreadyHeld  value ids the model already carries
     */
    public function ensureChoosable(?int $positionId, ?int $colourId, array $equipmentIds, array $alreadyHeld = []): void
    {
        $this->ensure(Dictionary::Position, $positionId, $alreadyHeld);
        $this->ensure(Dictionary::Colour, $colourId, $alreadyHeld);

        foreach ($equipmentIds as $equipmentId) {
            $this->ensure(Dictionary::Equipment, $equipmentId, $alreadyHeld);
        }
    }

    /** @param  list<int>  $alreadyHeld */
    private function ensure(Dictionary $dictionary, ?int $valueId, array $alreadyHeld): void
    {
        if ($valueId === null) {
            return;
        }

        $value = $this->values->find($valueId) ?? throw GlassModelRefused::valueDoesNotExist();

        if ($value->dictionary() !== $dictionary) {
            throw GlassModelRefused::valueIsOfAnotherDictionary();
        }

        if ($value->isWithdrawn() && ! in_array($valueId, $alreadyHeld, true)) {
            throw GlassModelRefused::valueIsWithdrawn($value->name()->value);
        }
    }
}
