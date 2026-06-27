<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Policies;

use App\Modules\Users\Application\Services\PermissionService;
use App\Modules\Users\Domain\Enums\PermissionEnum;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;

class UserPolicy
{
    public function __construct(
        private PermissionService $permissionService,
        private UserRepository $userRepository,
    ) {}

    public function assertCanUpdate(int|string $actorId, User $target): void
    {
        // Self-update: no DB load, always allowed
        if ((string) $actorId === (string) $target->getKey()) {
            return;
        }

        $actor = $this->userRepository->findOrFail($actorId);
        $this->permissionService->assertPermission($actor, PermissionEnum::UpdateAnyUser);
    }

    public function assertCanChangePassword(int|string $actorId, User $target): void
    {
        // Own password: always allowed
        if ((string) $actorId === (string) $target->getKey()) {
            return;
        }

        $actor = $this->userRepository->findOrFail($actorId);
        $this->permissionService->assertPermission($actor, PermissionEnum::ChangeAnyUserPassword);
    }

    public function assertCanDelete(int|string $actorId, User $target): void
    {
        $actor = $this->userRepository->findOrFail($actorId);
        $this->permissionService->assertPermission($actor, PermissionEnum::DeleteAnyUser);
    }

    public function assertCanCreate(int|string $actorId): void
    {
        $actor = $this->userRepository->findOrFail($actorId);
        $this->permissionService->assertPermission($actor, PermissionEnum::CreateUser);
    }

    public function assertCanAssignRole(int|string $actorId): void
    {
        $actor = $this->userRepository->findOrFail($actorId);
        $this->permissionService->assertPermission($actor, PermissionEnum::AssignUserRole);
    }
}
