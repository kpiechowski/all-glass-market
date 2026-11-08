<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_changes', function (Blueprint $table) {
            $table->id();
            $table->string('record_type');
            $table->unsignedBigInteger('record_id');
            $table->string('kind', 20);
            $table->string('title')->nullable();
            $table->json('changes')->nullable();
            $table->json('relation_changes')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('actor_reason')->nullable();
            $table->dateTime('occurred_at');

            $table->index(['record_type', 'record_id', 'occurred_at']);
            $table->index('occurred_at');
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_changes');
    }
};
