<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Application\ModuleApi;

use App\Core\Domain\Actor;
use App\Core\Domain\Preferences\PreferenceDefinitions;
use App\Modules\Preferences\Application\Commands\SavePreference\SavePreferenceCommand;
use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;
use App\Modules\Preferences\Domain\Repositories\UserPreferenceRepository;
use Ecotone\Modelling\CommandBus;

/**
 * How any module reads and writes a user's preference (ADR 0006). The key is one a module
 * registered as a `Core\Contracts\PreferenceDefinition`:
 *
 *     $density = $preferences->get($userId, 'table_density');           // the stored value or the default
 *     $preferences->save(Actor::user($userId), 'table_density', 'compact'); // the actor's own preference
 *
 * A save goes through the module's SavePreference command, so the definition's rules hold on
 * every path. Both methods refuse a key nobody registered.
 */
final class UserPreferences
{
    public function __construct(
        private CommandBus $commandBus,
        private PreferenceDefinitions $definitions,
        private UserPreferenceRepository $preferences,
    ) {}

    /** @throws PreferenceRefused when no module registered the key */
    public function get(int $userId, string $key): mixed
    {
        $definition = $this->definitions->find($key) ?? throw PreferenceRefused::unknownPreference();

        $preference = $this->preferences->findFor($userId, $key);

        return $preference === null ? $definition->default() : $preference->value();
    }

    /**
     * @param  string|int|float|bool|array<array-key, mixed>|null  $value
     *
     * @throws PreferenceRefused when the key is unknown, the actor is the system or the definition refuses the value
     */
    public function save(Actor $actor, string $key, string|int|float|bool|array|null $value): void
    {
        $this->commandBus->send(new SavePreferenceCommand($actor, $key, $value));
    }
}
