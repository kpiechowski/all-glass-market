<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** FR-30, ADR 0007: Offers' typed settings, one row. No row yet means the initial VAT rate (23%). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('vat_rate_basis_points');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_settings');
    }
};
