<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\UpdateUser;

use App\Core\Domain\Actor;

/**
 * FR-25. A null role keeps the current Role; a null password keeps the
 * current password (RULE-03). Without the manage Permission the actor can
 * only edit themself, and shipping text, role and password are ignored/refused.
 */
final readonly class UpdateUserCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $companyName = null,
        public ?string $companyNip = null,
        public ?string $companyAddress = null,
        public ?string $pickupAddress = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $shippingText = null,
        public ?string $role = null,
        public ?string $password = null,
    ) {}
}
