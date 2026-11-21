<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §8 Inquiry: Offer, sending admin, optional channel, note, state, when sent, read and answered; and the
 * Reservation's link to the Inquiry it came from. Foreign keys do not cascade: a permanent delete removes the
 * Inquiries in its handler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->restrictOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->string('channel')->nullable();
            $table->string('note', 500);
            $table->string('state')->index();
            $table->timestamp('sent_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });

        Schema::table('offer_reservations', function (Blueprint $table): void {
            $table->foreignId('inquiry_id')->nullable()->constrained('offer_inquiries')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('offer_reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inquiry_id');
        });

        Schema::dropIfExists('offer_inquiries');
    }
};
