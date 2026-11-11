<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Glass model and the parts always saved with it (ADR 0008). A Eurocode is unique across the
 * whole catalogue (RULE-01); an OEM code once per model by its compared form (RULE-04).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_glass_models', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('position_id')->constrained('catalogue_dictionary_values')->restrictOnDelete();
            $table->foreignId('colour_id')->nullable()->constrained('catalogue_dictionary_values')->restrictOnDelete();
            $table->boolean('withdrawn')->default(false);
            $table->timestamps();
        });

        Schema::create('catalogue_glass_model_eurocodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('glass_model_id')->constrained('catalogue_glass_models')->cascadeOnDelete();
            $table->string('code', 17)->unique();
            $table->boolean('is_main')->default(false);
        });

        Schema::create('catalogue_glass_model_oem_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('glass_model_id')->constrained('catalogue_glass_models')->cascadeOnDelete();
            $table->string('code');
            $table->string('code_key')->index();

            $table->unique(['glass_model_id', 'code_key']);
        });

        Schema::create('catalogue_glass_model_equipment', function (Blueprint $table): void {
            $table->foreignId('glass_model_id')->constrained('catalogue_glass_models')->cascadeOnDelete();
            $table->foreignId('dictionary_value_id')->constrained('catalogue_dictionary_values')->restrictOnDelete();

            $table->primary(['glass_model_id', 'dictionary_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_glass_model_equipment');
        Schema::dropIfExists('catalogue_glass_model_oem_codes');
        Schema::dropIfExists('catalogue_glass_model_eurocodes');
        Schema::dropIfExists('catalogue_glass_models');
    }
};
