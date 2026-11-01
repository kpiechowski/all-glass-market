<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenameRoleValuesOnUsersTableMigrationTest extends TestCase
{
    use MigratesStepByStep;
    use RefreshDatabase;

    private const string MIGRATION = '2026_11_01_000000_rename_role_values_on_users_table';

    public function test_it_renames_v1_role_values_to_v2_keys(): void
    {
        $this->rollBackThrough(self::MIGRATION);
        DB::table('users')->insert([
            $this->v1User('root@example.com', 'root'),
            $this->v1User('admin@example.com', 'admin'),
            $this->v1User('client@example.com', 'client'),
        ]);

        $this->migration(self::MIGRATION)->up();

        $this->assertSame(
            ['admin@example.com' => 'admin', 'client@example.com' => 'user', 'root@example.com' => 'super_admin'],
            DB::table('users')->orderBy('email')->pluck('role', 'email')->all(),
        );
    }

    public function test_new_rows_default_to_the_user_role(): void
    {
        $this->rollBackThrough(self::MIGRATION);

        $this->migration(self::MIGRATION)->up();
        DB::table('users')->insert(['name' => 'New', 'email' => 'new@example.com', 'password' => 'hash']);

        $this->assertSame('user', DB::table('users')->where('email', 'new@example.com')->value('role'));
    }

    /**
     * @return array{name: string, email: string, password: string, role: string}
     */
    private function v1User(string $email, string $role): array
    {
        return ['name' => $email, 'email' => $email, 'password' => 'hash', 'role' => $role];
    }
}
