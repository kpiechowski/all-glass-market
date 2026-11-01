<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CreateUser;

use App\Core\Domain\Actor;

/**
 * WF-04, temporary-password path. The Account type is always Private (FR-20).
 */
final readonly class CreateUserCommand
{
    public function __construct(
        public Actor $actor,
        public string $role,
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
        public ?string $companyName = null,
        public ?string $companyNip = null,
        public ?string $companyAddress = null,
        public ?string $pickupAddress = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $shippingText = null,
    ) {}
}
