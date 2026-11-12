<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Console;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\Infrastructure\Seeders\CatalogueTestDataSeeder;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeedCatalogueConsoleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_small_size_seeds_within_its_range(): void
    {
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();

        $this->assertSame(20, GlassModelModel::count());
        $this->assertSame(15, VehicleModel::count());
        $this->assertGreaterThan(30, DictionaryValueModel::count());
    }

    public function test_it_seeds_data_that_satisfies_the_rules(): void
    {
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();

        $this->assertSame(20, DB::table('catalogue_glass_model_eurocodes')->where('is_main', true)->distinct()->count('glass_model_id'));
        $this->assertSame(20, DB::table('catalogue_glass_model_eurocodes')->where('is_main', true)->count());
        $this->assertGreaterThan(20, DB::table('catalogue_glass_model_eurocodes')->count());
        $this->assertGreaterThan(0, DB::table('catalogue_glass_model_vehicles')->count());
        $this->assertGreaterThan(0, DB::table('catalogue_dictionary_values')->where('withdrawn', true)->count());
        $this->assertSame(
            6,
            DB::table('catalogue_dictionary_values')->distinct()->count('dictionary'),
        );
    }

    public function test_the_default_size_is_medium(): void
    {
        $this->artisan('seed-katalog', ['--force' => true])->assertSuccessful();

        $this->assertSame(200, GlassModelModel::count());
        $this->assertSame(80, VehicleModel::count());
    }

    public function test_the_large_size_seeds_its_range(): void
    {
        $this->artisan('seed-katalog', ['size' => 'l', '--force' => true])->assertSuccessful();

        $this->assertSame(2000, GlassModelModel::count());
        $this->assertSame(400, VehicleModel::count());
    }

    public function test_rerunning_ends_with_the_same_counts(): void
    {
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();
        $first = $this->counts();

        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();

        $this->assertSame($first, $this->counts());
    }

    public function test_a_smaller_rerun_replaces_the_bigger_data(): void
    {
        $this->artisan('seed-katalog', ['size' => 'm', '--force' => true])->assertSuccessful();
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();

        $this->assertSame(20, GlassModelModel::count());
    }

    public function test_it_refuses_an_invalid_size_and_changes_nothing(): void
    {
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();

        $this->artisan('seed-katalog', ['size' => 'xl', '--force' => true])->assertFailed();

        $this->assertSame(20, GlassModelModel::count());
    }

    public function test_it_refuses_the_production_environment_and_changes_nothing(): void
    {
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();
        $this->app['env'] = 'production';

        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertFailed();

        $this->app['env'] = 'testing';
        $this->assertSame(20, GlassModelModel::count());
    }

    public function test_it_wipes_only_after_confirmation(): void
    {
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();

        $this->artisan('seed-katalog', ['size' => 'm'])->expectsConfirmation(__('catalogue::console.seed_catalogue.confirm'), 'no')->assertFailed();

        $this->assertSame(20, GlassModelModel::count());
    }

    public function test_it_leaves_other_modules_tables_untouched(): void
    {
        $user = UserModel::factory()->create();

        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();
        $this->artisan('seed-katalog', ['size' => 's', '--force' => true])->assertSuccessful();

        $this->assertNotNull(UserModel::find($user->id));
    }

    public function test_the_wipe_covers_every_catalogue_table(): void
    {
        $tables = array_values(array_filter(
            array_column(Schema::getTables(), 'name'),
            fn (string $name): bool => str_starts_with($name, 'catalogue_'),
        ));

        $this->assertEqualsCanonicalizing($tables, CatalogueTestDataSeeder::TABLES);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return array_combine(
            CatalogueTestDataSeeder::TABLES,
            array_map(fn (string $table): int => DB::table($table)->count(), CatalogueTestDataSeeder::TABLES),
        );
    }
}
