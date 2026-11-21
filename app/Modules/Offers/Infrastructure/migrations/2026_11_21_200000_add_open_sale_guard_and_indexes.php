<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quality audit G12/G13. `is_open` is 1 while a Sale is not returned and null once it is, so the unique index on
 * (offer_id, is_open) lets the database refuse a second unreturned Sale of one Offer, the same way `is_current` does
 * for Reservations. Existing rows are backfilled: the latest unreturned Sale of each Offer is the open one, as the
 * repository read it before. Also indexes the columns the lists sort and filter by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_sales', function (Blueprint $table): void {
            $table->boolean('is_open')->nullable()->after('returned_by_id');
        });

        DB::statement(
            'UPDATE offer_sales SET is_open = 1 WHERE returned_at IS NULL AND id IN '
            .'(SELECT id FROM (SELECT MAX(id) AS id FROM offer_sales WHERE returned_at IS NULL GROUP BY offer_id) AS latest_open)',
        );

        Schema::table('offer_sales', function (Blueprint $table): void {
            $table->unique(['offer_id', 'is_open']);
            $table->index('sold_at');
        });

        Schema::table('offers', function (Blueprint $table): void {
            $table->index('first_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropIndex(['first_approved_at']);
        });

        Schema::table('offer_sales', function (Blueprint $table): void {
            $table->dropIndex(['sold_at']);
            $table->dropUnique(['offer_id', 'is_open']);
            $table->dropColumn('is_open');
        });
    }
};
