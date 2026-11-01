<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\ValueObjects\Email;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private function newUser(string $name = 'Jan'): User
    {
        return User::create($name, Email::from('jan@example.com'), 'user', 'hash');
    }

    public function test_a_new_user_is_private_without_an_id(): void
    {
        $user = $this->newUser();

        $this->assertNull($user->id());
        $this->assertSame(AccountType::Private, $user->accountType());
        $this->assertSame('user', $user->roleKey());
    }

    public function test_a_name_is_required(): void
    {
        $this->expectException(UserRefused::class);

        $this->newUser('  ');
    }

    public function test_a_name_longer_than_255_characters_is_refused(): void
    {
        $this->expectException(UserRefused::class);

        $this->newUser(str_repeat('a', 256));
    }

    public function test_it_updates_contact_and_company_data(): void
    {
        $user = $this->newUser();

        $user->updateData('Anna', Email::from('anna@example.com'), '123', 'Firma', '111', 'Adres', 'Odbiór', 'Łódź', '90-001');

        $this->assertSame('Anna', $user->name());
        $this->assertSame('anna@example.com', $user->email()->value());
        $this->assertSame('Firma', $user->companyName());
        $this->assertSame('90-001', $user->postalCode());
    }

    public function test_updating_data_validates_the_name(): void
    {
        $this->expectException(UserRefused::class);

        $this->newUser()->updateData('', Email::from('a@example.com'), null, null, null, null, null, null, null);
    }

    public function test_it_changes_shipping_text_role_and_password(): void
    {
        $user = $this->newUser();

        $user->changeShippingText('<p>Wysyłka</p>');
        $user->assignRole('admin');
        $user->changePasswordHash('newhash');

        $this->assertSame('<p>Wysyłka</p>', $user->shippingText());
        $this->assertSame('admin', $user->roleKey());
        $this->assertSame('newhash', $user->passwordHash());
    }

    private function companyReadyUser(): User
    {
        $user = $this->newUser();
        $user->updateData('Jan', Email::from('jan@example.com'), '123', 'Firma', '5252525252', 'ul. Szklana 1', null, null, null);

        return $user;
    }

    public function test_switching_to_company_records_the_declaration_and_starts_the_lock(): void
    {
        $now = CarbonImmutable::parse('2026-11-01 10:00:00');
        $user = $this->companyReadyUser();

        $changed = $user->changeAccountType(AccountType::Company, true, $now);

        $this->assertTrue($changed);
        $this->assertSame(AccountType::Company, $user->accountType());
        $this->assertEquals($now, $user->declarationAcceptedAt());
        $this->assertSame('V1', $user->declarationVersion());
        $this->assertEquals($now->addMinutes(30), $user->accountTypeLockedUntil());
    }

    public function test_company_without_the_accepted_declaration_is_refused(): void
    {
        $user = $this->companyReadyUser();

        $this->expectException(UserRefused::class);

        $user->changeAccountType(AccountType::Company, false, CarbonImmutable::now());
    }

    public function test_company_without_nip_or_address_is_refused(): void
    {
        $user = $this->newUser();

        $this->expectException(UserRefused::class);

        $user->changeAccountType(AccountType::Company, true, CarbonImmutable::now());
    }

    public function test_a_failed_switch_leaves_the_user_untouched(): void
    {
        $user = $this->newUser();

        try {
            $user->changeAccountType(AccountType::Company, true, CarbonImmutable::now());
        } catch (UserRefused) {
        }

        $this->assertSame(AccountType::Private, $user->accountType());
        $this->assertNull($user->accountTypeLockedUntil());
        $this->assertNull($user->declarationAcceptedAt());
    }

    public function test_switching_to_private_clears_the_acceptance(): void
    {
        $user = $this->companyReadyUser();
        $user->changeAccountType(AccountType::Company, true, CarbonImmutable::parse('2026-11-01 10:00:00'));

        $user->changeAccountType(AccountType::Private, false, CarbonImmutable::parse('2026-11-01 10:31:00'));

        $this->assertSame(AccountType::Private, $user->accountType());
        $this->assertNull($user->declarationAcceptedAt());
        $this->assertNull($user->declarationVersion());
    }

    public function test_a_change_inside_the_lock_is_refused_and_allowed_after_it(): void
    {
        $user = $this->companyReadyUser();
        $user->changeAccountType(AccountType::Company, true, CarbonImmutable::parse('2026-11-01 10:00:00'));

        try {
            $user->changeAccountType(AccountType::Private, false, CarbonImmutable::parse('2026-11-01 10:29:59'));
            $this->fail('The lock should refuse the change.');
        } catch (UserRefused) {
            $this->assertSame(AccountType::Company, $user->accountType());
        }

        $user->changeAccountType(AccountType::Private, false, CarbonImmutable::parse('2026-11-01 10:30:00'));

        $this->assertSame(AccountType::Private, $user->accountType());
    }

    public function test_choosing_the_current_type_changes_nothing(): void
    {
        $user = $this->newUser();

        $this->assertFalse($user->changeAccountType(AccountType::Private, false, CarbonImmutable::now()));
        $this->assertNull($user->accountTypeLockedUntil());
    }

    public function test_a_company_without_recorded_acceptance_reads_as_private(): void
    {
        $user = User::reconstitute(
            1, 'Jan', Email::from('jan@example.com'), null, null, '1', 'a', null, null, null, null,
            AccountType::Company, 'user', 'hash',
        );

        $this->assertSame(AccountType::Private, $user->accountType());
    }

    public function test_a_company_cannot_lose_its_nip_or_address(): void
    {
        $user = $this->companyReadyUser();
        $user->changeAccountType(AccountType::Company, true, CarbonImmutable::now());

        $user->updateData('Jan', Email::from('jan@example.com'), null, null, '', 'ul. Szklana 1', null, null, null);

        $this->expectException(UserRefused::class);

        $user->assertConsistent();
    }

    public function test_a_new_user_is_active(): void
    {
        $this->assertTrue($this->newUser()->isActive());
        $this->assertNull($this->newUser()->deactivatedAt());
    }

    public function test_deactivation_records_the_moment_and_reactivation_clears_it(): void
    {
        $user = $this->newUser();
        $now = CarbonImmutable::parse('2026-11-01 10:00:00');

        $user->deactivate($now);
        $this->assertFalse($user->isActive());
        $this->assertTrue($user->deactivatedAt()->equalTo($now));

        $user->reactivate();
        $this->assertTrue($user->isActive());
    }

    public function test_deactivating_a_deactivated_user_or_reactivating_an_active_one_is_refused(): void
    {
        $user = $this->newUser();

        try {
            $user->reactivate();
            $this->fail('Reactivating an active User was allowed');
        } catch (UserRefused) {
            $this->assertTrue($user->isActive());
        }

        $user->deactivate(CarbonImmutable::now());

        $this->expectException(UserRefused::class);
        $user->deactivate(CarbonImmutable::now());
    }
}
