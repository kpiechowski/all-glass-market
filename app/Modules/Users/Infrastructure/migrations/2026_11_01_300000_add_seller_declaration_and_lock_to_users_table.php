<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('seller_declaration_accepted_at')->nullable()->after('account_type');
            $table->string('seller_declaration_version')->nullable()->after('seller_declaration_accepted_at');
            $table->timestamp('account_type_changed_at')->nullable()->after('seller_declaration_version');
        });

        // D-47: migrated Company Users accepted on the migration date, version "V1".
        DB::table('users')->where('account_type', 'company')->update([
            'seller_declaration_accepted_at' => now(),
            'seller_declaration_version' => 'V1',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['seller_declaration_accepted_at', 'seller_declaration_version', 'account_type_changed_at']);
        });
    }
};
