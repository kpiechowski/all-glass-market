<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offers (§8). `number` is unique (RULE-13). The Glass model and producer are plain ids of
 * Catalogue's records, with no foreign key across the module line: Catalogue's own guards decide
 * when they may be removed. The price is kept in grosze.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 10)->unique();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('glass_model_id')->nullable()->index();
            $table->string('identification_hint')->nullable();
            $table->unsignedBigInteger('producer_id')->nullable()->index();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('condition')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('description')->nullable();
            $table->text('private_notes')->nullable();
            $table->unsignedBigInteger('price_grosze')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
