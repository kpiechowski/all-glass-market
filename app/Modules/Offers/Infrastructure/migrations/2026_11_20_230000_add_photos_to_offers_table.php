<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-05: the ordered storage paths of the public photos (the first is the main one) and of the
 * private ones. Public photos sit on the public disk, private ones on a disk with no URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->json('public_photos')->nullable();
            $table->json('private_photos')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropColumn(['public_photos', 'private_photos']);
        });
    }
};
