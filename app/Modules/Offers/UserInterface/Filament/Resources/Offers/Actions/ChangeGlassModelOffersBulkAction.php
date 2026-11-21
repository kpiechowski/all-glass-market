<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPickerSlideOver;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\PickerVariant;
use App\Modules\Offers\Application\Commands\ChangeGlassModelOfOffers\ChangeGlassModelOfOffersCommand;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\BulkActionResult;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;

/** FR-14, RULE-14, D-44, template E (slide-over): an admin picks a Glass model for the selected Offers; their statuses stay. */
final class ChangeGlassModelOffersBulkAction
{
    public static function make(): BulkAction
    {
        /** @var BulkAction $action */
        $action = GlassModelPickerSlideOver::configure(
            BulkAction::make('changeGlassModelSelected')
                ->label(__('offers::resources.offer.actions.change_glass_model_selected.label'))
                ->color('gray'),
            variant: PickerVariant::Admin,
        );

        return $action
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.change_glass_model_selected.submit'))
            ->visible(fn (): bool => CurrentActor::can(OffersPermission::ManageAll))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data): void {
                BulkActionResult::notify('change_glass_model_selected', CommandSender::send(new ChangeGlassModelOfOffersCommand(
                    CurrentActor::get(),
                    BulkActionResult::ids($records),
                    (int) $data[GlassModelPickerSlideOver::STATE_KEY],
                )));
            });
    }
}
