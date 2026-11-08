<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\ReactivateUserAction;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\UserRecordMenu;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/** Template B (form); the password is set through the "⋮" action, not a form section. */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public static bool $formActionsAreSticky = true;

    /** No primary in the header (Zapisz sits in the sticky footer); Podgląd and Aktywuj ponownie are secondary. */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ReactivateUserAction::make(),
            UserRecordMenu::make(),
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
            extraPermissions: isset($data['extra_permissions']) ? array_values($data['extra_permissions']) : null,
        ), ['users::exceptions.company_data_missing' => ['data.company_nip', 'data.company_address']]);

        return $record->refresh();
    }
}
