<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Infrastructure\Persistence;

use App\Core\Exceptions\ReadOnlyModelException;
use Tests\Fixtures\Core\CreatesWidgetsTable;
use Tests\Fixtures\Core\WidgetModel;
use Tests\Fixtures\Core\WidgetModelFactory;
use Tests\TestCase;

class ModelFactoryTest extends TestCase
{
    use CreatesWidgetsTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createWidgetsTable();
    }

    public function test_create_persists_a_read_only_model(): void
    {
        $widget = WidgetModelFactory::new()->create(['name' => 'gear']);

        $this->assertSame('gear', WidgetModel::findOrFail($widget->id)->name);
        $this->assertCount(3, WidgetModelFactory::new()->count(3)->create());
    }

    public function test_the_model_stays_read_only_after_the_factory_created_it(): void
    {
        $widget = WidgetModelFactory::new()->create();

        $this->expectException(ReadOnlyModelException::class);

        $widget->update(['name' => 'changed']);
    }
}
