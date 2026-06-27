<?php

declare(strict_types=1);

namespace App\Core\Http\Cli;

use App\Modules\Users\Domain\Enums\RoleEnum;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Console\Command;

class CreateRootCommand extends Command
{
    protected $signature = 'dev:create-root';

    protected $description = 'Create or update a root user with a given email and password';

    public function handle(): int
    {
        $email = $this->ask('Email', 'root@example.com');
        $password = $this->secret('Password (leave blank to use "password")') ?: 'password';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Root',
                'password' => $password, // hashed cast handles hashing
                'email_verified_at' => now(),
            ],
        );

        // role is excluded from fillable — must be set directly
        $user->role = RoleEnum::Root;
        $user->save();

        $action = $user->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->info("{$action} root user: {$email}");

        return self::SUCCESS;
    }
}
