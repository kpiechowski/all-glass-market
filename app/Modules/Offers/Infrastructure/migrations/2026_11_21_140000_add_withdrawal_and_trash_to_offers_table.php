<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §8: when the Offer went to the Trash (null outside it; the status is kept, D-30) and whether a seller
 * changed more than price and private material since the last approval, which decides resuming (FR-24).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->timestamp('trashed_at')->nullable()->index();
            $table->boolean('changed_since_approval')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropIndex(['trashed_at']);
            $table->dropColumn(['trashed_at', 'changed_since_approval']);
        });
    }
};
