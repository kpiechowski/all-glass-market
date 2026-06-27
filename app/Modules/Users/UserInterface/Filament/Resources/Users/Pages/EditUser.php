<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Core\Concerns\HasCommandBus;
use App\Modules\Users\Application\Commands\AssignUserRole\AssignUserRoleCommand;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Domain\Enums\RoleEnum;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    use HasCommandBus;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updated = $this->commandBus->send(new UpdateUserCommand(
            actorId: Auth::id(),
            id: $record->getKey(),
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
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

        if (isset($data['role']) && RoleEnum::from($data['role']) !== $record->role) {
            $this->commandBus->send(new AssignUserRoleCommand(
                actorId: Auth::id(),
                userId: $record->getKey(),
                role: RoleEnum::from($data['role']),
            ));
        }

        return $updated->fresh();
    }
}
