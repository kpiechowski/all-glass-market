<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\DeleteOffers\DeleteOffersCommand;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\BulkActionResult;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;

/** FR-25, RULE-14, template E (dangerous): an admin moves the selected Offers to the Trash; Reserved ones are skipped. */
final class DeleteOffersBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('deleteSelected')
            ->label(__('offers::resources.offer.actions.delete_selected.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.delete_selected.heading'))
            ->modalDescription(__('offers::resources.offer.actions.delete_selected.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.delete_selected.label'))
            ->visible(fn ($livewire): bool => CurrentActor::can(OffersPermission::ManageAll)
                && OfferActionVisibility::bulkApplies($livewire->activeTab ?? null, fn (OfferStatus $status): bool => OfferDomainPolicy::canBeTrashed($status, false)))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                BulkActionResult::notify('delete_selected', CommandSender::send(new DeleteOffersCommand(CurrentActor::get(), BulkActionResult::ids($records))));
            });
    }
}
