<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\ModuleApi;

/**
 * A relation whose added and removed items are recorded (FR-03): its label
 * (a translation key or plain text) and how one item is named.
 */
final readonly class TrackedRelation
{
    public function __construct(
        public string $label,
        public ValuePresenter $item,
    ) {}
}
