<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/** Template B (form). */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public static bool $formActionsAreSticky = true;

    public function getTitle(): string|Htmlable
    {
        return __('users::resources.user.titles.create');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $id = CommandSender::send(new CreateUserCommand(
            actor: CurrentActor::get(),
            role: $data['role'],
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            phone: $data['phone'] ?? null,
            companyName: $data['company_name'] ?? null,
            companyNip: $data['company_nip'] ?? null,
            companyAddress: $data['company_address'] ?? null,
            pickupAddress: $data['shipment_address'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['city_code'] ?? null,
            shippingText: $data['shipping_note'] ?? null,
            extraPermissions: array_values($data['extra_permissions'] ?? []),
            sendInvitation: ($data['password_method'] ?? 'temporary') === 'invitation',
        ));

        return UserModel::findOrFail($id);
    }
}
