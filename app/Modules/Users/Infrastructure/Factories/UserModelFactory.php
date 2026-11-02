<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends ModelFactory<UserModel> */
final class UserModelFactory extends ModelFactory
{
    protected $model = UserModel::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRole(string $roleKey): static
    {
        return $this->state(fn (array $attributes) => ['role' => $roleKey]);
    }

    /** RULE-05: Company comes with NIP, address and a recorded acceptance. */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'company',
            'company_nip' => '5252525252',
            'company_address' => 'ul. Szklana 1, Warszawa',
            'seller_declaration_accepted_at' => now(),
            'seller_declaration_version' => 'V1',
        ]);
    }

    public function accountTypeChangedAt(\DateTimeInterface $at): static
    {
        return $this->state(fn (array $attributes) => ['account_type_changed_at' => $at]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => ['deactivated_at' => now()]);
    }

    public function passwordChangeRequired(): static
    {
        return $this->state(fn (array $attributes) => ['password_change_required' => true]);
    }

    public function admin(): static
    {
        return $this->withRole('admin');
    }

    public function superAdmin(): static
    {
        return $this->withRole('super_admin');
    }
}
