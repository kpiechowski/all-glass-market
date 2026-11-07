<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends ModelFactory<UserPreferenceModel> */
final class UserPreferenceModelFactory extends ModelFactory
{
    protected $model = UserPreferenceModel::class;

    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'key' => 'quick_access',
            'value' => [],
        ];
    }
}
