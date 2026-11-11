<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vehicles (FR-07); `name_key` is the lowercased name that RULE-09 makes unique. Make and body point at dictionary values, which cannot be deleted while used (FR-24). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('name_key')->unique();
            $table->foreignId('make_id')->constrained('catalogue_dictionary_values')->restrictOnDelete();
            $table->string('model')->nullable();
            $table->foreignId('body_id')->nullable()->constrained('catalogue_dictionary_values')->restrictOnDelete();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->string('generation')->nullable();
            $table->string('argic_code', 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_vehicles');
    }
};
