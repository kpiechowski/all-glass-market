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
            $table->string('account_type')->default('private')->after('role');
        });

        DB::table('users')->where('company_account', true)->where('has_accepted_terms', true)->update(['account_type' => 'company']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company_account', 'has_accepted_terms']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('company_account')->default(false);
            $table->boolean('has_accepted_terms')->default(false);
        });

        DB::table('users')->where('account_type', 'company')->update(['company_account' => true, 'has_accepted_terms' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
