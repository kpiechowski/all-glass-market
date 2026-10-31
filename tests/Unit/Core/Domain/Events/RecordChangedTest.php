<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Domain\Events;

use App\Core\Domain\Events\RecordChanged;
use Error;
use PHPUnit\Framework\TestCase;

class RecordChangedTest extends TestCase
{
    public function test_it_carries_the_model_class_id_and_old_and_new_values(): void
    {
        $event = new RecordChanged('App\\Offer', 5, ['price' => ['old' => 10, 'new' => 20]]);

        $this->assertSame('App\\Offer', $event->modelClass);
        $this->assertSame(5, $event->id);
        $this->assertSame(['price' => ['old' => 10, 'new' => 20]], $event->changes);
    }

    public function test_it_is_immutable(): void
    {
        $event = new RecordChanged('App\\Offer', 5, []);

        $this->expectException(Error::class);

        $event->id = 6;
    }
}
