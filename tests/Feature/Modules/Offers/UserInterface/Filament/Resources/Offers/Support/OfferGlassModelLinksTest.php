<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\ListGlassModels;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferGlassModelLinks;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Catalogue FR-04: "N ofert" on a Glass model card counts Offers outside the Trash, every status, in one query. */
class OfferGlassModelLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_every_status_but_not_the_trash_and_leaves_out_models_without_offers(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $empty = GlassModelModelFactory::new()->create();
        $trashedOnly = GlassModelModelFactory::new()->create();
        OfferModelFactory::new()->withGlassModel($model->id)->count(2)->create();
        OfferModelFactory::new()->withGlassModel($model->id)->status(OfferStatus::Active)->create();
        OfferModelFactory::new()->withGlassModel($model->id)->inTrash()->create();
        OfferModelFactory::new()->withGlassModel($trashedOnly->id)->inTrash()->create();
        $this->actingAs(UserModel::factory()->admin()->create());

        $links = app(OfferGlassModelLinks::class)->linksFor([$model->id, $empty->id, $trashedOnly->id]);

        $this->assertSame([$model->id], array_keys($links));
        $this->assertSame('3 oferty', $links[$model->id]->label);
        $this->assertStringContainsString("filters%5Bglass_model%5D%5Bglass_model_id%5D={$model->id}", $links[$model->id]->url);
    }

    public function test_a_seller_counts_only_their_own_offers(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $seller = UserModel::factory()->create();
        OfferModelFactory::new()->withGlassModel($model->id)->ownedBy($seller->id)->create();
        OfferModelFactory::new()->withGlassModel($model->id)->count(2)->create();
        $this->actingAs($seller);

        $links = app(OfferGlassModelLinks::class)->linksFor([$model->id]);

        $this->assertSame('1 oferta', $links[$model->id]->label);
    }

    public function test_the_label_takes_the_polish_plural_form(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        foreach ([1 => '1 oferta', 3 => '3 oferty', 5 => '5 ofert'] as $count => $label) {
            $model = GlassModelModelFactory::new()->create();
            OfferModelFactory::new()->withGlassModel($model->id)->count($count)->create();

            $this->assertSame($label, app(OfferGlassModelLinks::class)->linksFor([$model->id])[$model->id]->label);
        }
    }

    public function test_the_glass_model_list_shows_the_link_with_one_count_query_for_the_page(): void
    {
        $models = GlassModelModelFactory::new()->count(3)->create();
        foreach ($models as $model) {
            OfferModelFactory::new()->withGlassModel($model->id)->create();
        }
        $this->actingAs(UserModel::factory()->admin()->create());

        DB::enableQueryLog();
        Livewire::test(ListGlassModels::class)->assertSee('1 oferta');
        $countQueries = collect(DB::getQueryLog())->filter(fn (array $q): bool => str_contains($q['query'], 'offers_count'));

        $this->assertCount(1, $countQueries);
    }
}
