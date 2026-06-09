<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Core\Concerns\HasCommandBus;
use App\Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    use HasCommandBus;

    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->commandBus->send(new CreateUserCommand(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
        ));
    }
}
