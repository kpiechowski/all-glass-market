<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\ValueObjects\Email;

/**
 * A person with an account (spec §8). Active state, "password change required",
 * Extra Permissions and the declaration come with later tickets.
 */
final class User
{
    private function __construct(
        private ?int $id,
        private string $name,
        private Email $email,
        private ?string $phone,
        private ?string $companyName,
        private ?string $companyNip,
        private ?string $companyAddress,
        private ?string $pickupAddress,
        private ?string $city,
        private ?string $postalCode,
        private ?string $shippingText,
        private AccountType $accountType,
        private string $roleKey,
        private string $passwordHash,
    ) {
        self::assertName($name);
    }

    public static function create(string $name, Email $email, string $roleKey, string $passwordHash): self
    {
        return new self(null, $name, $email, null, null, null, null, null, null, null, null, AccountType::Private, $roleKey, $passwordHash);
    }

    /** @internal for the mapper */
    public static function reconstitute(
        int $id,
        string $name,
        Email $email,
        ?string $phone,
        ?string $companyName,
        ?string $companyNip,
        ?string $companyAddress,
        ?string $pickupAddress,
        ?string $city,
        ?string $postalCode,
        ?string $shippingText,
        AccountType $accountType,
        string $roleKey,
        string $passwordHash,
    ): self {
        return new self($id, $name, $email, $phone, $companyName, $companyNip, $companyAddress, $pickupAddress, $city, $postalCode, $shippingText, $accountType, $roleKey, $passwordHash);
    }

    public function updateData(
        string $name,
        Email $email,
        ?string $phone,
        ?string $companyName,
        ?string $companyNip,
        ?string $companyAddress,
        ?string $pickupAddress,
        ?string $city,
        ?string $postalCode,
    ): void {
        self::assertName($name);

        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->companyName = $companyName;
        $this->companyNip = $companyNip;
        $this->companyAddress = $companyAddress;
        $this->pickupAddress = $pickupAddress;
        $this->city = $city;
        $this->postalCode = $postalCode;
    }

    public function changeShippingText(?string $shippingText): void
    {
        $this->shippingText = $shippingText;
    }

    public function assignRole(string $roleKey): void
    {
        $this->roleKey = $roleKey;
    }

    public function changePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    private static function assertName(string $name): void
    {
        if (trim($name) === '' || mb_strlen($name) > 255) {
            throw UserRefused::invalidName();
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function companyNip(): ?string
    {
        return $this->companyNip;
    }

    public function companyAddress(): ?string
    {
        return $this->companyAddress;
    }

    public function pickupAddress(): ?string
    {
        return $this->pickupAddress;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function postalCode(): ?string
    {
        return $this->postalCode;
    }

    public function shippingText(): ?string
    {
        return $this->shippingText;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }

    public function roleKey(): string
    {
        return $this->roleKey;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }
}
