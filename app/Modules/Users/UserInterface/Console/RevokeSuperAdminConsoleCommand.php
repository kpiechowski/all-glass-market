<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Console;

use App\Core\Domain\Actor;
use App\Core\Exceptions\DomainException;
use App\Modules\Users\Application\Commands\RevokeSuperAdmin\RevokeSuperAdminCommand;
use Ecotone\Modelling\CommandBus;
use Illuminate\Console\Command;

class RevokeSuperAdminConsoleCommand extends Command
{
    protected $signature = 'users:revoke-super-admin {email : E-mail of the Super Admin}';

    protected $description = 'Revoke the Super Admin Role from a User; they become a plain User';

    public function handle(CommandBus $bus): int
    {
        try {
            $bus->send(new RevokeSuperAdminCommand(Actor::system('console: users:revoke-super-admin'), (string) $this->argument('email')));
        } catch (DomainException $exception) {
            $this->error(__($exception->translationKey, $exception->parameters));

            return self::FAILURE;
        }

        $this->info('Super Admin revoked from '.$this->argument('email'));

        return self::SUCCESS;
    }
}
