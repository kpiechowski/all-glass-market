<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrationsRoundTripTest extends TestCase
{
    use MigratesStepByStep;
    use RefreshDatabase;

    /** Creates `users`; rolling it back would drop the row the round trip carries. */
    private const string USERS_TABLE = '0001_01_01_000000_create_users_table';

    public function test_every_migration_after_the_users_table_rolls_back_and_reapplies_with_a_user_row(): void
    {
        DB::table('users')->insert([
            'name' => 'Root', 'email' => 'root@example.com', 'password' => 'hash', 'role' => 'super_admin', 'account_type' => 'company',
        ]);
        $names = array_keys($this->migrationFiles());
        $later = array_slice($names, array_search(self::USERS_TABLE, $names, true) + 1);

        foreach (array_reverse($later) as $name) {
            $this->migration($name)->down();
        }
        foreach ($later as $name) {
            $this->migration($name)->up();
        }

        $user = DB::table('users')->where('email', 'root@example.com')->first();
        // add_role's down() drops the column, so the role comes back as the column default.
        $this->assertSame('user', $user->role);
        $this->assertSame('company', $user->account_type);
        $this->assertSame('V1', $user->seller_declaration_version);
    }
}
