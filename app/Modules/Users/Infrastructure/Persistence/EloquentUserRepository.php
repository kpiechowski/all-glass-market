<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Infrastructure\Persistence\Mappers\UserMapper;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends EloquentRepository<User> */
final class EloquentUserRepository extends EloquentRepository implements UserRepository
{
    public function __construct(private readonly UserMapper $userMapper, private readonly ChangeRecorder $recorder)
    {
        parent::__construct(UserModel::class, $userMapper);
    }

    public function add(object $entity): int
    {
        $id = parent::add($entity);
        $this->recordExtraPermissionChange($id, [], $entity->extraPermissions());

        return $id;
    }

    public function save(object $entity): void
    {
        $before = $this->extraPermissionsOf($entity->id());

        parent::save($entity);

        $this->recordExtraPermissionChange($entity->id(), $before, $entity->extraPermissions());
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

    public function extraPermissionsOf(int $userId): array
    {
        $model = $this->query()->whereKey($userId)->first(['id', 'extra_permissions']);

        return $model === null ? [] : array_values((array) ($model->extra_permissions ?? []));
    }

    public function userIdsWithRoles(array $roleKeys): array
    {
        return $this->query()->whereIn('role', $roleKeys)->whereNull('deactivated_at')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Extra Permissions are a JSON list, not a pivot: report the added and
     * removed keys as a relation change so Audit shows them as a list.
     *
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    private function recordExtraPermissionChange(int $id, array $before, array $after): void
    {
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added === [] && $removed === []) {
            return;
        }

        $this->recorder->recordRelations($this->query()->findOrFail($id), ['extra_permissions' => ['added' => $added, 'removed' => $removed]]);
    }
}
