<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changes_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->nullOnDelete();
            $table->morphs('loggable');
            $table->json('changes');
            $table->text('message');
            $table->json('meta_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changes_logs');
    }
};
