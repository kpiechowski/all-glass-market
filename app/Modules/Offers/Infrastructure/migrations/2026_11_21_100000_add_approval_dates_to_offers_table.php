<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** §8: when the Offer was first approved (the chart's "added") and last approved (the "changes since" block, resuming). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->timestamp('first_approved_at')->nullable();
            $table->timestamp('last_approved_at')->nullable();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['first_approved_at', 'last_approved_at']);
        });
    }
};
