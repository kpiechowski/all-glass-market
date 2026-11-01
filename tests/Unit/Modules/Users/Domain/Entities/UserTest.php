<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\ValueObjects\Email;
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
}
