<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Support;

use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Policies\UserManagementPolicy;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * How Roles read in the panel: from the RoleRegistry, so a Role of another
 * module shows by its own label and a Role nobody registers shows as
 * "nieznana rola" instead of failing (FR-22, D-20).
 */
final class RoleDisplay
{
    public static function label(?string $roleKey): string
    {
        $role = $roleKey === null ? null : app(RoleRegistry::class)->roleFor($roleKey);

        return $role?->label() ?? __('users::resources.user.unknown_role');
    }

    public static function color(?string $roleKey): string
    {
        if ($roleKey === null || ! app(RoleRegistry::class)->isRegistered($roleKey)) {
            return 'danger';
        }

        return UserRole::tryFrom($roleKey)?->getColor() ?? 'gray';
    }

    /**
     * Roles the signed-in User may give: registered, never Super Admin, none above their own rank.
     *
     * @return array<string, string>
     */
    public static function assignableOptions(): array
    {
        $registry = app(RoleRegistry::class);
        $actorRank = self::actorRank();
        $options = [];

        foreach ($registry->roles() as $role) {
            $key = (string) $role->value;

            if (UserManagementPolicy::canAssignRole($key, $registry->rankOf($key), $actorRank)) {
                $options[$key] = $role->label();
            }
        }

        return $options;
    }

    /** Every registered Role, for the list filter. */
    public static function allOptions(): array
    {
        $options = [];

        foreach (app(RoleRegistry::class)->roles() as $role) {
            $options[(string) $role->value] = $role->label();
        }

        return $options;
    }

    public static function actorRank(): int
    {
        $user = auth()->user();

        return $user instanceof UserModel ? (app(RoleRegistry::class)->rankOf((string) $user->role) ?? 0) : 0;
    }

    public static function canManage(UserModel $target): bool
    {
        return UserManagementPolicy::canManage(
            self::actorRank(),
            app(RoleRegistry::class)->rankOf((string) $target->role) ?? 0,
        );
    }

    /** RULE-08, D-47: the Deactivate action is absent for oneself, a Super Admin and anyone above the actor. */
    public static function canDeactivate(UserModel $target): bool
    {
        $actor = auth()->user();

        return $actor instanceof UserModel && UserManagementPolicy::canDeactivate(
            (int) $actor->getKey(),
            (int) $target->getKey(),
            (string) $target->role,
            self::actorRank(),
            app(RoleRegistry::class)->rankOf((string) $target->role) ?? 0,
        );
    }
}
