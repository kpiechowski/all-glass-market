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

    public function admin(): static
    {
        return $this->withRole('admin');
    }

    public function superAdmin(): static
    {
        return $this->withRole('super_admin');
    }
}
