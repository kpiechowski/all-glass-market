<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §8 Reservation and Sale. A Reservation or Sale belongs to its Offer (foreign key, no cascade: a permanent
 * delete removes them in its handler). `is_current` is 1 while a Reservation is current and null once it ended,
 * so a unique index on (offer_id, is_current) backs RULE-05: at most one current Reservation per Offer.
 * A Sale keeps the entered price, VAT rate (basis points) and buyer's price at the moment of sale (RULE-09).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->boolean('made_by_admin');
            $table->string('note', 500);
            $table->timestamp('made_at');
            $table->string('ended_as')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->unique(['offer_id', 'is_current']);
        });

        Schema::create('offer_sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->restrictOnDelete();
            $table->string('channel');
            $table->unsignedBigInteger('sale_price_grosze');
            $table->unsignedBigInteger('entered_price_grosze');
            $table->unsignedSmallInteger('vat_rate_basis_points');
            $table->unsignedBigInteger('buyers_price_grosze');
            $table->foreignId('marked_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('sold_at');
            $table->timestamp('returned_at')->nullable();
            $table->text('return_reason')->nullable();
            $table->foreignId('returned_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_sales');
        Schema::dropIfExists('offer_reservations');
    }
};
