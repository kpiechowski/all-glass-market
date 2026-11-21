<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\WithdrawOffers\WithdrawOffersCommand;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\BulkActionResult;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;

/** FR-24, RULE-14: withdraws the selected Offers (a seller's own, an admin's any) and reports how many it skipped. */
final class WithdrawOffersBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('withdrawSelected')
            ->label(__('offers::resources.offer.actions.withdraw_selected.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.withdraw_selected.heading'))
            ->modalDescription(__('offers::resources.offer.actions.withdraw_selected.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.withdraw_selected.label'))
            ->visible(fn (): bool => CurrentActor::can(OffersPermission::Create))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                BulkActionResult::notify('withdraw_selected', CommandSender::send(new WithdrawOffersCommand(CurrentActor::get(), BulkActionResult::ids($records))));
            });
    }
}
