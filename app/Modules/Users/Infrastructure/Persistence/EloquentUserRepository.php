<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Infrastructure\Persistence\Mappers\UserMapper;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends EloquentRepository<User> */
final class EloquentUserRepository extends EloquentRepository implements UserRepository
{
    public function __construct(private readonly UserMapper $userMapper)
    {
        parent::__construct(UserModel::class, $userMapper);
    }

    public function exists(int $id): bool
    {
        return $this->query()->whereKey($id)->exists();
    }

    public function findByEmail(string $email): ?User
    {
        $model = $this->query()->where('email', mb_strtolower(trim($email)))->first();

        return $model === null ? null : $this->userMapper->toEntity($model);
    }

    public function emailIsTaken(string $email, ?int $exceptUserId = null): bool
    {
        return $this->query()
            ->where('email', mb_strtolower(trim($email)))
            ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->exists();
    }

    public function roleKeyOf(int $userId): ?string
    {
        $role = $this->query()->whereKey($userId)->value('role');

        return $role === null ? null : (string) $role;
    }

    public function userIdsWithRoles(array $roleKeys): array
    {
        return $this->query()->whereIn('role', $roleKeys)->whereNull('deactivated_at')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
