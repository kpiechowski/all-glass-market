<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesWidgetsTable
{
    protected function createWidgetsTable(): void
    {
        Schema::create('widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }
}
