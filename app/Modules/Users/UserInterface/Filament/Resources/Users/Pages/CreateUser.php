<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Core\Concerns\HasCommandBus;
use App\Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    use HasCommandBus;

    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->commandBus->send(new CreateUserCommand(
            actorId: Auth::id(),
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'] ?? null,
            companyAccount: (bool) ($data['company_account'] ?? false),
            hasAcceptedTerms: (bool) ($data['has_accepted_terms'] ?? false),
            companyName: $data['company_name'] ?? null,
            companyNip: $data['company_nip'] ?? null,
            companyAddress: $data['company_address'] ?? null,
            shipmentAddress: $data['shipment_address'] ?? null,
            city: $data['city'] ?? null,
            cityCode: $data['city_code'] ?? null,
            shippingInformation: $data['shipping_information'] ?? null,
        ));
    }
}
