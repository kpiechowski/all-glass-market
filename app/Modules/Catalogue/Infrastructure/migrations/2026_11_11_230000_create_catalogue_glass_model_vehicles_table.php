<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitment (FR-10): which vehicles a Glass model fits. It records nothing else. Deleting a vehicle
 * deletes its fitments (FR-11), deleting a Glass model its fitment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_glass_model_vehicles', function (Blueprint $table): void {
            $table->foreignId('glass_model_id')->constrained('catalogue_glass_models')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('catalogue_vehicles')->cascadeOnDelete();

            $table->primary(['glass_model_id', 'vehicle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_glass_model_vehicles');
    }
};
