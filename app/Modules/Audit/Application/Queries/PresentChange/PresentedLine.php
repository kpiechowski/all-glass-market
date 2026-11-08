<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\PresentChange;

/**
 * One line of an entry's details (FR-10): a field with its old and new value,
 * or a relation with the items added and removed.
 */
final readonly class PresentedLine
{
    /**
     * @param  list<PresentedValue>  $added
     * @param  list<PresentedValue>  $removed
     */
    public function __construct(
        public string $label,
        public ?PresentedValue $old = null,
        public ?PresentedValue $new = null,
        public array $added = [],
        public array $removed = [],
    ) {}
}
