<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Infrastructure\Persistence;

use App\Core\Exceptions\ReadOnlyModelException;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Fixtures\Core\CreatesWidgetsTable;
use Tests\Fixtures\Core\WidgetModel;
use Tests\TestCase;

class ReadOnlyModelTest extends TestCase
{
    use CreatesWidgetsTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createWidgetsTable();
    }

    private function storedWidget(): WidgetModel
    {
        return ReadOnlyModel::writing(fn () => WidgetModel::create(['name' => 'gear']));
    }

    public function test_it_refuses_to_save_outside_the_write_scope(): void
    {
        $this->expectException(ReadOnlyModelException::class);

        (new WidgetModel(['name' => 'gear']))->save();
    }

    public function test_it_refuses_create_update_delete_push_and_force_delete_outside_the_write_scope(): void
    {
        $widget = $this->storedWidget();

        $attempts = [
            'create' => fn () => WidgetModel::create(['name' => 'x']),
            'update' => fn () => $widget->update(['name' => 'x']),
            'delete' => fn () => $widget->delete(),
            'push' => fn () => $widget->push(),
            'forceDelete' => fn () => $widget->forceDelete(),
        ];

        foreach ($attempts as $name => $attempt) {
            try {
                $attempt();
                $this->fail("{$name}() was not refused");
            } catch (ReadOnlyModelException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(1, WidgetModel::count());
        $this->assertSame('gear', $widget->fresh()->name);
    }

    public function test_writes_pass_inside_the_write_scope_and_return_the_callback_result(): void
    {
        $widget = $this->storedWidget();

        $result = ReadOnlyModel::writing(function () use ($widget) {
            $widget->update(['name' => 'cog']);
            $widget->push();
            $widget->delete();

            return 'done';
        });

        $this->assertSame('done', $result);
        $this->assertSame(0, WidgetModel::count());
    }

    public function test_the_scope_closes_after_the_callback_even_when_it_throws(): void
    {
        try {
            ReadOnlyModel::writing(fn () => throw new RuntimeException('boom'));
        } catch (RuntimeException) {
        }

        $this->expectException(ReadOnlyModelException::class);

        (new WidgetModel(['name' => 'gear']))->save();
    }

    public function test_nested_scopes_keep_the_outer_scope_open(): void
    {
        ReadOnlyModel::writing(function (): void {
            ReadOnlyModel::writing(fn () => null);

            WidgetModel::create(['name' => 'still open']);
        });

        $this->assertSame(1, WidgetModel::count());
    }

    /**
     * @return array<string, array{0: callable(WidgetModel): mixed}>
     */
    public static function builderWrites(): array
    {
        return [
            'builder update' => [fn (WidgetModel $w) => WidgetModel::query()->whereKey($w->id)->update(['name' => 'x'])],
            'builder delete' => [fn (WidgetModel $w) => WidgetModel::query()->whereKey($w->id)->delete()],
            'builder forceDelete' => [fn (WidgetModel $w) => WidgetModel::query()->whereKey($w->id)->forceDelete()],
            'builder insert' => [fn () => WidgetModel::query()->insert(['name' => 'x'])],
            'builder insertGetId' => [fn () => WidgetModel::query()->insertGetId(['name' => 'x'])],
            'builder insertOrIgnore' => [fn () => WidgetModel::query()->insertOrIgnore(['name' => 'x'])],
            'builder upsert' => [fn () => WidgetModel::query()->upsert([['id' => 1, 'name' => 'x']], ['id'], ['name'])],
            'builder updateOrInsert' => [fn () => WidgetModel::query()->updateOrInsert(['name' => 'gear'], ['name' => 'x'])],
            'builder increment' => [fn (WidgetModel $w) => WidgetModel::query()->whereKey($w->id)->increment('stock')],
            'builder decrement' => [fn (WidgetModel $w) => WidgetModel::query()->whereKey($w->id)->decrement('stock')],
            'model increment' => [fn (WidgetModel $w) => $w->increment('stock')],
            'model decrement' => [fn (WidgetModel $w) => $w->decrement('stock')],
            'destroy' => [fn (WidgetModel $w) => WidgetModel::destroy($w->id)],
        ];
    }

    #[DataProvider('builderWrites')]
    public function test_query_builder_writes_are_refused_outside_the_write_scope(callable $write): void
    {
        $widget = $this->storedWidget();

        try {
            $write($widget);
            $this->fail('The write was not refused');
        } catch (ReadOnlyModelException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(1, WidgetModel::count());
        $this->assertSame('gear', $widget->fresh()->name);
        $this->assertSame(0, $widget->fresh()->stock);
    }

    #[DataProvider('builderWrites')]
    public function test_query_builder_writes_pass_inside_the_write_scope(callable $write): void
    {
        $widget = $this->storedWidget();

        ReadOnlyModel::writing(fn () => $write($widget));

        $this->addToAssertionCount(1);
    }

    public function test_reads_are_untouched(): void
    {
        $this->storedWidget();

        $this->assertSame(1, WidgetModel::query()->where('name', 'gear')->count());
        $this->assertTrue(WidgetModel::query()->where('name', 'gear')->exists());
        $this->assertSame('gear', WidgetModel::query()->firstOrFail()->name);
        $this->assertCount(1, WidgetModel::query()->paginate(10)->items());
    }
}
