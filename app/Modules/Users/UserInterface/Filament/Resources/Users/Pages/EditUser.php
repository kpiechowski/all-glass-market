<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use App\Modules\Users\UserInterface\Support\KeepsOwnSessionAfterPasswordChange;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        CommandSender::send(new UpdateUserCommand(
            actor: CurrentActor::get(),
            userId: $record->getKey(),
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            companyName: $data['company_name'] ?? null,
            companyNip: $data['company_nip'] ?? null,
            companyAddress: $data['company_address'] ?? null,
            pickupAddress: $data['shipment_address'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['city_code'] ?? null,
            shippingText: $data['shipping_note'] ?? null,
            role: $data['role'] ?? null,
            password: $data['password'] ?? null,
        ));

        if (filled($data['password'] ?? null)) {
            KeepsOwnSessionAfterPasswordChange::for((int) $record->getKey());
        }

        return $record->refresh();
    }
}
