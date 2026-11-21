<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Migrations;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Modules\Users\Infrastructure\Migrations\MigratesStepByStep;
use Tests\TestCase;

class AddOpenSaleGuardAndIndexesMigrationTest extends TestCase
{
    use MigratesStepByStep;
    use RefreshDatabase;

    private const string MIGRATION = '2026_11_21_200000_add_open_sale_guard_and_indexes';

    public function test_backfill_marks_only_the_latest_unreturned_sale_of_each_offer_as_open(): void
    {
        $this->rollBackThrough(self::MIGRATION);
        $userId = $this->legacyUser();
        $returnedOffer = $this->legacyOffer($userId, 'OFR0000001');
        $singleOffer = $this->legacyOffer($userId, 'OFR0000002');
        $duplicatedOffer = $this->legacyOffer($userId, 'OFR0000003');
        $returnedSale = $this->legacySale($returnedOffer, $userId, returned: true);
        $singleSale = $this->legacySale($singleOffer, $userId);
        $olderDuplicate = $this->legacySale($duplicatedOffer, $userId);
        $newerDuplicate = $this->legacySale($duplicatedOffer, $userId);

        $this->migration(self::MIGRATION)->up();

        $this->assertSame(
            [
                $returnedSale => null,
                $singleSale => 1,
                $olderDuplicate => null,
                $newerDuplicate => 1,
            ],
            array_map(
                static fn (mixed $value): ?int => $value === null ? null : (int) $value,
                DB::table('offer_sales')->orderBy('id')->pluck('is_open', 'id')->all(),
            ),
        );
    }

    public function test_the_database_refuses_a_second_open_sale_of_one_offer(): void
    {
        $this->rollBackThrough(self::MIGRATION);
        $userId = $this->legacyUser();
        $offerId = $this->legacyOffer($userId, 'OFR0000001');
        $this->legacySale($offerId, $userId);
        $this->migration(self::MIGRATION)->up();

        $this->expectException(QueryException::class);

        DB::table('offer_sales')->insert($this->saleRow($offerId, $userId) + ['is_open' => 1]);
    }

    public function test_a_returned_sale_does_not_block_a_new_open_sale(): void
    {
        $this->rollBackThrough(self::MIGRATION);
        $userId = $this->legacyUser();
        $offerId = $this->legacyOffer($userId, 'OFR0000001');
        $this->legacySale($offerId, $userId, returned: true);
        $this->migration(self::MIGRATION)->up();

        DB::table('offer_sales')->insert($this->saleRow($offerId, $userId) + ['is_open' => 1]);

        $this->assertSame(1, DB::table('offer_sales')->where('is_open', 1)->count());
    }

    public function test_down_removes_the_column_and_the_indexes(): void
    {
        $this->rollBackThrough(self::MIGRATION);
        $this->migration(self::MIGRATION)->up();
        $this->assertTrue(Schema::hasColumn('offer_sales', 'is_open'));
        $this->assertTrue(Schema::hasIndex('offer_sales', ['offer_id', 'is_open'], 'unique'));
        $this->assertTrue(Schema::hasIndex('offer_sales', ['sold_at']));
        $this->assertTrue(Schema::hasIndex('offers', ['first_approved_at']));

        $this->migration(self::MIGRATION)->down();

        $this->assertFalse(Schema::hasColumn('offer_sales', 'is_open'));
        $this->assertFalse(Schema::hasIndex('offer_sales', ['offer_id', 'is_open']));
        $this->assertFalse(Schema::hasIndex('offer_sales', ['sold_at']));
        $this->assertFalse(Schema::hasIndex('offers', ['first_approved_at']));
    }

    private function legacyUser(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Legacy',
            'email' => 'legacy@example.com',
            'password' => 'hash',
            'role' => 'user',
        ]);
    }

    private function legacyOffer(int $ownerId, string $number): int
    {
        return DB::table('offers')->insertGetId([
            'number' => $number,
            'owner_id' => $ownerId,
            'status' => 'sold',
        ]);
    }

    private function legacySale(int $offerId, int $userId, bool $returned = false): int
    {
        return DB::table('offer_sales')->insertGetId($this->saleRow($offerId, $userId, $returned));
    }

    /**
     * @return array<string, mixed>
     */
    private function saleRow(int $offerId, int $userId, bool $returned = false): array
    {
        return [
            'offer_id' => $offerId,
            'channel' => 'allglass',
            'sale_price_grosze' => 10000,
            'entered_price_grosze' => 10000,
            'vat_rate_basis_points' => 2300,
            'buyers_price_grosze' => 10000,
            'marked_by_id' => $userId,
            'sold_at' => '2026-11-01 10:00:00',
            'returned_at' => $returned ? '2026-11-02 10:00:00' : null,
        ];
    }
}
