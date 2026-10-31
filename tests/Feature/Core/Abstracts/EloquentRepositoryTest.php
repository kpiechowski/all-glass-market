<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Abstracts;

use App\Core\Contracts\Repository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\Fixtures\Core\CreatesWidgetsTable;
use Tests\Fixtures\Core\Widget;
use Tests\Fixtures\Core\WidgetModel;
use Tests\Fixtures\Core\WidgetModelFactory;
use Tests\Fixtures\Core\WidgetRepository;
use Tests\TestCase;

class EloquentRepositoryTest extends TestCase
{
    use CreatesWidgetsTable;

    private Repository $widgets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createWidgetsTable();
        $this->widgets = new WidgetRepository;
    }

    public function test_add_persists_the_entity_and_returns_its_id(): void
    {
        $id = $this->widgets->add(Widget::create('gear'));

        $this->assertSame('gear', WidgetModel::findOrFail($id)->name);
    }

    public function test_get_and_find_return_the_entity(): void
    {
        $id = WidgetModelFactory::new()->create(['name' => 'gear'])->id;

        $this->assertSame('gear', $this->widgets->get($id)->name());
        $this->assertSame($id, $this->widgets->find($id)->id());
    }

    public function test_find_returns_null_and_get_throws_for_a_missing_id(): void
    {
        $this->assertNull($this->widgets->find(99));

        $this->expectException(ModelNotFoundException::class);

        $this->widgets->get(99);
    }

    public function test_save_writes_changes_of_one_entity_and_leaves_others_alone(): void
    {
        $id = WidgetModelFactory::new()->create(['name' => 'gear'])->id;
        $otherId = WidgetModelFactory::new()->create(['name' => 'cog'])->id;

        $widget = $this->widgets->get($id);
        $widget->rename('sprocket');
        $this->widgets->save($widget);

        $this->assertSame('sprocket', WidgetModel::findOrFail($id)->name);
        $this->assertSame('cog', WidgetModel::findOrFail($otherId)->name);
    }

    public function test_remove_deletes_only_that_entity(): void
    {
        $id = WidgetModelFactory::new()->create(['name' => 'gear'])->id;
        $otherId = WidgetModelFactory::new()->create(['name' => 'cog'])->id;

        $this->widgets->remove($this->widgets->get($id));

        $this->assertNull(WidgetModel::find($id));
        $this->assertNotNull(WidgetModel::find($otherId));
    }

    public function test_adapters_query_through_the_protected_query_entry_point(): void
    {
        WidgetModelFactory::new()->create(['name' => 'gear']);

        $this->assertSame('gear', $this->widgets->named('gear')->name());
        $this->assertNull($this->widgets->named('nope'));
    }
}
