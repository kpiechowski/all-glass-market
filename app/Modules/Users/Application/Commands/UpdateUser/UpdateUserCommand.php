<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\UpdateUser;

readonly class UpdateUserCommand
{
    public function __construct(
        public int|string $actorId,
        public int|string $id,
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?string $phone = null,
        public bool $companyAccount = false,
        public bool $hasAcceptedTerms = false,
        public ?string $companyName = null,
        public ?string $companyNip = null,
        public ?string $companyAddress = null,
        public ?string $shipmentAddress = null,
        public ?string $city = null,
        public ?string $cityCode = null,
        public ?string $shippingNote = null,
    ) {}
}
