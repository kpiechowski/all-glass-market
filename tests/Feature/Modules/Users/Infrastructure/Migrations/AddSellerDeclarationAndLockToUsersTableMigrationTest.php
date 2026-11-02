<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddSellerDeclarationAndLockToUsersTableMigrationTest extends TestCase
{
    use MigratesStepByStep;
    use RefreshDatabase;

    private const string MIGRATION = '2026_11_01_300000_add_seller_declaration_and_lock_to_users_table';

    public function test_migrated_company_users_accept_the_v1_declaration_on_the_migration_date(): void
    {
        Carbon::setTestNow('2026-11-01 12:00:00');
        $this->rollBackThrough(self::MIGRATION);
        DB::table('users')->insert([
            $this->existingUser('company@example.com', 'company'),
            $this->existingUser('private@example.com', 'private'),
        ]);

        $this->migration(self::MIGRATION)->up();

        $company = DB::table('users')->where('email', 'company@example.com')->first();
        $this->assertSame('2026-11-01 12:00:00', Carbon::parse($company->seller_declaration_accepted_at)->toDateTimeString());
        $this->assertSame('V1', $company->seller_declaration_version);
        $this->assertNull($company->account_type_changed_at);

        $private = DB::table('users')->where('email', 'private@example.com')->first();
        $this->assertNull($private->seller_declaration_accepted_at);
        $this->assertNull($private->seller_declaration_version);
        $this->assertNull($private->account_type_changed_at);
    }

    /**
     * @return array{name: string, email: string, password: string, role: string, account_type: string}
     */
    private function existingUser(string $email, string $accountType): array
    {
        return ['name' => $email, 'email' => $email, 'password' => 'hash', 'role' => 'user', 'account_type' => $accountType];
    }
}
