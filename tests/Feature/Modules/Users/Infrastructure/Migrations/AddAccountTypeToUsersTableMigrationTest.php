<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddAccountTypeToUsersTableMigrationTest extends TestCase
{
    use MigratesStepByStep;
    use RefreshDatabase;

    private const string MIGRATION = '2026_11_01_100000_add_account_type_to_users_table';

    public function test_only_a_company_account_that_accepted_the_terms_becomes_a_company(): void
    {
        $this->rollBackThrough(self::MIGRATION);
        DB::table('users')->insert([
            $this->v1User('both@example.com', companyAccount: true, acceptedTerms: true),
            $this->v1User('no-terms@example.com', companyAccount: true, acceptedTerms: false),
            $this->v1User('terms-only@example.com', companyAccount: false, acceptedTerms: true),
            $this->v1User('neither@example.com', companyAccount: false, acceptedTerms: false),
        ]);

        $this->migration(self::MIGRATION)->up();

        $this->assertSame(
            [
                'both@example.com' => 'company',
                'neither@example.com' => 'private',
                'no-terms@example.com' => 'private',
                'terms-only@example.com' => 'private',
            ],
            DB::table('users')->orderBy('email')->pluck('account_type', 'email')->all(),
        );
        $this->assertFalse(Schema::hasColumn('users', 'company_account'));
        $this->assertFalse(Schema::hasColumn('users', 'has_accepted_terms'));
    }

    /**
     * @return array{name: string, email: string, password: string, role: string, company_account: bool, has_accepted_terms: bool}
     */
    private function v1User(string $email, bool $companyAccount, bool $acceptedTerms): array
    {
        return [
            'name' => $email,
            'email' => $email,
            'password' => 'hash',
            'role' => 'user',
            'company_account' => $companyAccount,
            'has_accepted_terms' => $acceptedTerms,
        ];
    }
}
