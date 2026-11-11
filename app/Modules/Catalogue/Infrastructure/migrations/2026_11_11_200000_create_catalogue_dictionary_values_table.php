<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The six dictionaries in one table (FR-12); `name_key` is the lowercased name that RULE-08 makes unique per dictionary. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_dictionary_values', function (Blueprint $table): void {
            $table->id();
            $table->string('dictionary', 32);
            $table->string('name');
            $table->string('name_key');
            $table->boolean('withdrawn')->default(false);
            $table->timestamps();

            $table->unique(['dictionary', 'name_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_dictionary_values');
    }
};
