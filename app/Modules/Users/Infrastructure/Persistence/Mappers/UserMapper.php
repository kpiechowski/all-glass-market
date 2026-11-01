<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Mappers;

use App\Core\Contracts\Mapper;
use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\ValueObjects\Email;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** @implements Mapper<User> */
final class UserMapper implements Mapper
{
    public function toEntity(Model $model): User
    {
        /** @var UserModel $model */
        return User::reconstitute(
            $model->id,
            $model->name,
            Email::from($model->email),
            $model->phone,
            $model->company_name,
            $model->company_nip,
            $model->company_address,
            $model->shipment_address,
            $model->city,
            $model->city_code,
            $model->shipping_note,
            $model->account_type,
            $model->role,
            $model->getRawOriginal('password'),
            $model->seller_declaration_accepted_at === null ? null : CarbonImmutable::instance($model->seller_declaration_accepted_at),
            $model->seller_declaration_version,
            $model->account_type_changed_at === null ? null : CarbonImmutable::instance($model->account_type_changed_at),
        );
    }

    /** @return array<string, mixed> */
    public function toRow(object $entity): array
    {
        return [
            'name' => $entity->name(),
            'email' => $entity->email()->value(),
            'phone' => $entity->phone(),
            'company_name' => $entity->companyName(),
            'company_nip' => $entity->companyNip(),
            'company_address' => $entity->companyAddress(),
            'shipment_address' => $entity->pickupAddress(),
            'city' => $entity->city(),
            'city_code' => $entity->postalCode(),
            'shipping_note' => $entity->shippingText(),
            'account_type' => $entity->accountType(),
            'role' => $entity->roleKey(),
            'password' => $entity->passwordHash(),
            'seller_declaration_accepted_at' => $entity->declarationAcceptedAt(),
            'seller_declaration_version' => $entity->declarationVersion(),
            'account_type_changed_at' => $entity->accountTypeChangedAt(),
        ];
    }
}
