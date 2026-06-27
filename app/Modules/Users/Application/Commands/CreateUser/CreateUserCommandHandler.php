<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CreateUser;

use App\Modules\Users\Application\Policies\UserPolicy;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class CreateUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPolicy $userPolicy,
    ) {}

    #[CommandHandler]
    public function handle(CreateUserCommand $command): User
    {
        $this->userPolicy->assertCanCreate($command->actorId);

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
            'shipping_information' => $command->shippingInformation,
        ]);
    }
}
