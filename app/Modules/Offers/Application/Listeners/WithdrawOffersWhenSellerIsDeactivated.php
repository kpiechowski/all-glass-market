<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Listeners;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\WithdrawOffersOfDeactivatedSeller\WithdrawOffersOfDeactivatedSellerCommand;
use App\Modules\Users\Domain\Events\UserDeactivated;
use Ecotone\Modelling\CommandBus;

/** FR-35, WF-09: a deactivated seller's Offers are withdrawn by the System. Reactivation is not listened to: it restores nothing. */
final class WithdrawOffersWhenSellerIsDeactivated
{
    public function __construct(private CommandBus $commands) {}

    public function handle(UserDeactivated $event): void
    {
        $this->commands->send(new WithdrawOffersOfDeactivatedSellerCommand(
            Actor::system('seller deactivated'),
            $event->userId,
        ));
    }
}
