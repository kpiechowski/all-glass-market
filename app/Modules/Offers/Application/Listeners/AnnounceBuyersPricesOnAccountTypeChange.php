<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Listeners;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\AnnounceBuyersPriceChanges\AnnounceBuyersPriceChangesCommand;
use App\Modules\Users\Domain\Events\AccountTypeChanged;
use Ecotone\Modelling\CommandBus;

/** FR-28: a new Account type reprices the owner's Offers at once; adverts follow the announcement. */
final class AnnounceBuyersPricesOnAccountTypeChange
{
    public function __construct(private CommandBus $commands) {}

    public function handle(AccountTypeChanged $event): void
    {
        $this->commands->send(new AnnounceBuyersPriceChangesCommand(
            Actor::system('buyers-price-after-account-type-change'),
            $event->userId,
            $event->previousAccountType,
            $event->accountType,
        ));
    }
}
