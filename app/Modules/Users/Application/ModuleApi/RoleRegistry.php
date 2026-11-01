<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

use App\Core\Contracts\Permission;
use App\Modules\Users\Domain\Contracts\Role;

/**
 * Where modules declare their Roles, Permissions and grants while the
 * application boots (FR-02). It lives in memory only: a module that is not
 * installed declares nothing, so its Roles and grants do not exist. There is
 * no central role-to-permission map.
 */
final class RoleRegistry
{
    /** Roles of other modules rank at the User level unless they say otherwise. */
    public const int DEFAULT_RANK = 100;

    /** @var array<string, array{role: Role, rank: int, every: bool}> */
    private array $roles = [];

    /** @var array<string, array<string, true>> role key => permission value => true */
    private array $grants = [];

    /** @var list<class-string<Permission>> */
    private array $permissionEnums = [];

    public function declareRole(Role $role, int $rank = self::DEFAULT_RANK, bool $holdsEveryPermission = false): void
    {
        $this->roles[(string) $role->value] = ['role' => $role, 'rank' => $rank, 'every' => $holdsEveryPermission];
    }

    /**
     * @param  class-string<Permission>  $permissionEnum
     */
    public function declarePermissions(string $permissionEnum): void
    {
        if (! in_array($permissionEnum, $this->permissionEnums, true)) {
            $this->permissionEnums[] = $permissionEnum;
        }
    }

    public function grant(Role $role, Permission ...$permissions): void
    {
        foreach ($permissions as $permission) {
            $this->grants[(string) $role->value][(string) $permission->value] = true;
        }
    }

    public function isRegistered(string $roleKey): bool
    {
        return isset($this->roles[$roleKey]);
    }

    public function rankOf(string $roleKey): ?int
    {
        return $this->roles[$roleKey]['rank'] ?? null;
    }

    /**
     * Whether the Role was granted the Permission. An unregistered Role holds nothing.
     */
    public function roleHolds(string $roleKey, Permission $permission): bool
    {
        if (! $this->isRegistered($roleKey)) {
            return false;
        }

        return $this->roles[$roleKey]['every'] || isset($this->grants[$roleKey][(string) $permission->value]);
    }

    /**
     * @return list<Role>
     */
    public function roles(): array
    {
        return array_values(array_map(fn (array $entry): Role => $entry['role'], $this->roles));
    }

    /**
     * Every Permission a module declared.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        $all = [];

        foreach ($this->permissionEnums as $enum) {
            array_push($all, ...$enum::cases());
        }

        return $all;
    }
}
