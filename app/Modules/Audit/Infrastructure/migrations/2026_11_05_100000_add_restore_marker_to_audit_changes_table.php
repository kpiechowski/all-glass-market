<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_changes', function (Blueprint $table) {
            // No foreign key: retention may remove the source entry, and the marker keeps its date.
            $table->unsignedBigInteger('restored_from_id')->nullable()->after('occurred_at');
            $table->dateTime('restored_from_at')->nullable()->after('restored_from_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_changes', function (Blueprint $table) {
            $table->dropColumn(['restored_from_id', 'restored_from_at']);
        });
    }
};
