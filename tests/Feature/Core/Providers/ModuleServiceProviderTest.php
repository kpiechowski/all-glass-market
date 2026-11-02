<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\Fixtures\Core\MorphModule\Infrastructure\Persistence\Models\GadgetModel;
use Tests\Fixtures\Core\MorphModule\MorphModuleServiceProvider;
use Tests\TestCase;

class ModuleServiceProviderTest extends TestCase
{
    public function test_models_under_infrastructure_persistence_are_in_the_morph_map_without_the_model_suffix(): void
    {
        $this->app->register(MorphModuleServiceProvider::class);

        $this->assertSame(GadgetModel::class, Relation::getMorphedModel('gadget'));
    }
}
