<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'root')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'client')->update(['role' => 'user']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'root']);
        DB::table('users')->where('role', 'user')->update(['role' => 'client']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->change();
        });
    }
};
