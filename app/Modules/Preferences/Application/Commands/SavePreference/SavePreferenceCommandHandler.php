<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Application\Commands\SavePreference;

use App\Core\Domain\Preferences\PreferenceDefinitions;
use App\Modules\Preferences\Domain\Entities\UserPreference;
use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;
use App\Modules\Preferences\Domain\Repositories\UserPreferenceRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * ADR 0006: a user saves only their own preferences, so the actor is the owner and needs no
 * permission; the system, which is nobody, is refused. The definition registered under the key
 * validates and normalises the value; a key nobody registered is refused.
 */
final class SavePreferenceCommandHandler
{
    public function __construct(
        private PreferenceDefinitions $definitions,
        private UserPreferenceRepository $preferences,
    ) {}

    #[CommandHandler]
    public function handle(SavePreferenceCommand $command): void
    {
        $userId = $command->actor->id ?? throw PreferenceRefused::usersOnly();

        $definition = $this->definitions->find($command->key) ?? throw PreferenceRefused::unknownPreference();
        $value = $definition->normalise($command->value);

        $preference = $this->preferences->findFor($userId, $command->key);

        if ($preference === null) {
            $this->preferences->add(UserPreference::choose($userId, $command->key, $value));

            return;
        }

        $preference->change($value);
        $this->preferences->save($preference);
    }
}
