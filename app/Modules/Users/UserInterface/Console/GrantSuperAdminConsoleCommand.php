<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Console;

use App\Core\Domain\Actor;
use App\Core\Exceptions\DomainException;
use App\Modules\Users\Application\Commands\GrantSuperAdmin\GrantSuperAdminCommand;
use Ecotone\Modelling\CommandBus;
use Illuminate\Console\Command;

class GrantSuperAdminConsoleCommand extends Command
{
    protected $signature = 'users:grant-super-admin {email : E-mail of the existing User}';

    protected $description = 'Grant the Super Admin Role to a User (the only way to do it)';

    public function handle(CommandBus $bus): int
    {
        try {
            $bus->send(new GrantSuperAdminCommand(Actor::system('users::actors.console_grant_super_admin'), (string) $this->argument('email')));
        } catch (DomainException $exception) {
            $this->error(__($exception->translationKey, $exception->parameters));

            return self::FAILURE;
        }

        $this->info('Super Admin granted to '.$this->argument('email'));

        return self::SUCCESS;
    }
}
