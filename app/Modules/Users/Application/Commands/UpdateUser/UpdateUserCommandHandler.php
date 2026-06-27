<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\UpdateUser;

use App\Modules\Users\Application\Policies\UserPolicy;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class UpdateUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPolicy $userPolicy,
    ) {}

    #[CommandHandler]
    public function handle(UpdateUserCommand $command): User
    {
        $user = $this->userRepository->findOrFail($command->id);

        $this->userPolicy->assertCanUpdate($command->actorId, $user);

        $attributes = [
            'name' => $command->name,
            'email' => $command->email,
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
        ];

        if ($command->password !== null) {
            $attributes['password'] = $command->password;
        }

        return $this->userRepository->update($user, $attributes);
    }
}
