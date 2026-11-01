<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Persistence\Mappers;

use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\ValueObjects\Email;
use App\Modules\Users\Infrastructure\Persistence\Mappers\UserMapper;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserMapperTest extends TestCase
{
    public function test_it_round_trips_an_entity_through_a_model(): void
    {
        $mapper = new UserMapper;
        $user = User::reconstitute(
            7, 'Jan Kowalski', Email::from('jan@example.com'), '123', 'Firma', '111', 'Adres', 'Odbiór', 'Łódź', '90-001',
            '<p>Wysyłka</p>', AccountType::Private, 'admin', Hash::make('secret'),
        );

        $model = new UserModel;
        $model->forceFill($mapper->toRow($user))->setAttribute('id', 7);
        $model->syncOriginal();

        $this->assertEquals($user, $mapper->toEntity($model));
    }
}
