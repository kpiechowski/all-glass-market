<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Preferences\Infrastructure\Persistence\Mappers;

use App\Modules\Preferences\Domain\Entities\UserPreference;
use App\Modules\Preferences\Infrastructure\Persistence\Mappers\UserPreferenceMapper;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;
use Tests\TestCase;

class UserPreferenceMapperTest extends TestCase
{
    public function test_it_round_trips_a_list_value(): void
    {
        $this->assertRoundTrip(UserPreference::reconstitute(4, 9, 'quick_access', ['audit.log', 'users.list']));
    }

    public function test_it_round_trips_an_empty_list(): void
    {
        $this->assertRoundTrip(UserPreference::reconstitute(5, 9, 'quick_access', []));
    }

    public function test_it_round_trips_a_scalar_value(): void
    {
        $this->assertRoundTrip(UserPreference::reconstitute(6, 9, 'density', 'compact'));
    }

    private function assertRoundTrip(UserPreference $preference): void
    {
        $mapper = new UserPreferenceMapper;

        $model = new UserPreferenceModel;
        $model->forceFill($mapper->toRow($preference))->setAttribute('id', $preference->id());
        $model->syncOriginal();

        $this->assertEquals($preference, $mapper->toEntity($model));
    }
}
