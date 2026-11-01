<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\ValueObjects\Email;
use Carbon\CarbonImmutable;

/**
 * A person with an account (spec §8). "Password change required"
 * and Extra Permissions come with later tickets.
 */
final class User
{
    /** RULE-04, D-47: the version of the Seller declaration wording on offer today. */
    public const string CURRENT_DECLARATION_VERSION = 'V1';

    /** RULE-07: minutes the Account type stays locked after a saved change. */
    public const int ACCOUNT_TYPE_LOCK_MINUTES = 30;

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
        private ?CarbonImmutable $declarationAcceptedAt = null,
        private ?string $declarationVersion = null,
        private ?CarbonImmutable $accountTypeChangedAt = null,
        private ?CarbonImmutable $deactivatedAt = null,
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
        ?CarbonImmutable $declarationAcceptedAt = null,
        ?string $declarationVersion = null,
        ?CarbonImmutable $accountTypeChangedAt = null,
        ?CarbonImmutable $deactivatedAt = null,
    ): self {
        return new self($id, $name, $email, $phone, $companyName, $companyNip, $companyAddress, $pickupAddress, $city, $postalCode, $shippingText, $accountType, $roleKey, $passwordHash, $declarationAcceptedAt, $declarationVersion, $accountTypeChangedAt, $deactivatedAt);
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

    /**
     * FR-17, RULE-04/05/07. Returns whether the type changed; choosing the
     * current type changes nothing. Company needs the NIP and address already
     * on the User and an accepted declaration; Private clears the acceptance.
     * A refusal leaves the entity as it was.
     */
    public function changeAccountType(AccountType $type, bool $declarationAccepted, CarbonImmutable $now): bool
    {
        if ($type === $this->accountType()) {
            return false;
        }

        $lockedUntil = $this->accountTypeLockedUntil();

        if ($lockedUntil !== null && $now->lessThan($lockedUntil)) {
            throw UserRefused::accountTypeLocked($lockedUntil);
        }

        if ($type === AccountType::Company) {
            self::assertCompanyData($this->companyNip, $this->companyAddress);

            if (! $declarationAccepted) {
                throw UserRefused::declarationNotAccepted();
            }

            $this->declarationAcceptedAt = $now;
            $this->declarationVersion = self::CURRENT_DECLARATION_VERSION;
        } else {
            $this->declarationAcceptedAt = null;
            $this->declarationVersion = null;
        }

        $this->accountType = $type;
        $this->accountTypeChangedAt = $now;

        return true;
    }

    /**
     * RULE-04 on the resulting state: call after data and Account type were
     * applied, before saving. Only a Company is bound to keep NIP and address.
     */
    public function assertConsistent(): void
    {
        if ($this->accountType() === AccountType::Company) {
            self::assertCompanyData($this->companyNip, $this->companyAddress);
        }
    }

    private static function assertCompanyData(?string $nip, ?string $address): void
    {
        if (trim((string) $nip) === '' || trim((string) $address) === '') {
            throw UserRefused::companyDataMissing();
        }
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

    /** WF-05: the User keeps all their data; only the state changes. */
    public function deactivate(CarbonImmutable $now): void
    {
        if (! $this->isActive()) {
            throw UserRefused::alreadyDeactivated();
        }

        $this->deactivatedAt = $now;
    }

    public function reactivate(): void
    {
        if ($this->isActive()) {
            throw UserRefused::notDeactivated();
        }

        $this->deactivatedAt = null;
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

    /** RULE-05: Company only while the acceptance is recorded. */
    public function accountType(): AccountType
    {
        return $this->accountType === AccountType::Company && $this->declarationAcceptedAt !== null
            ? AccountType::Company
            : AccountType::Private;
    }

    public function declarationAcceptedAt(): ?CarbonImmutable
    {
        return $this->declarationAcceptedAt;
    }

    public function declarationVersion(): ?string
    {
        return $this->declarationVersion;
    }

    public function accountTypeChangedAt(): ?CarbonImmutable
    {
        return $this->accountTypeChangedAt;
    }

    /** RULE-07: null when nothing locks the Account type. */
    public function accountTypeLockedUntil(): ?CarbonImmutable
    {
        return $this->accountTypeChangedAt?->addMinutes(self::ACCOUNT_TYPE_LOCK_MINUTES);
    }

    public function isActive(): bool
    {
        return $this->deactivatedAt === null;
    }

    public function deactivatedAt(): ?CarbonImmutable
    {
        return $this->deactivatedAt;
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
