<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Catalogue settings (FR-15-FR-17): how a position, colour or equipment value is drawn. One row per mapped value; the columns used depend on its dictionary. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_thumbnail_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dictionary_value_id')->unique()->constrained('catalogue_dictionary_values')->cascadeOnDelete();
            $table->string('dictionary', 32);
            $table->string('shape', 32)->nullable();
            $table->boolean('mirrored')->default(false);
            $table->string('fill', 7)->nullable();
            $table->unsignedTinyInteger('opacity')->nullable();
            $table->string('pictogram', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_thumbnail_mappings');
    }
};
