<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CreateUser;

use App\Core\Contracts\Authorizer;
use App\Core\Domain\Actor;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class CreateUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private Authorizer $authorizer,
    ) {}

    #[CommandHandler]
    public function handle(CreateUserCommand $command): User
    {
        $this->authorizer->ensure(Actor::user((int) $command->actorId), UsersPermission::ManageUsers);

        return $this->userRepository->create([
            'name' => $command->name,
            'email' => $command->email,
            'password' => $command->password,
            'phone' => $command->phone,
            'company_account' => $command->companyAccount,
            'has_accepted_terms' => $command->hasAcceptedTerms,
            'company_name' => $command->companyName,
            'company_nip' => $command->companyNip,
            'company_address' => $command->companyAddress,
            'shipment_address' => $command->shipmentAddress,
            'city' => $command->city,
            'city_code' => $command->cityCode,
            'shipping_note' => $command->shippingNote,
        ]);
    }
}
