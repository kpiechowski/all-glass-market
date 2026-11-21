<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferWithGlassModelAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\DeleteOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\DeleteOfferPermanentlyAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\RejectOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\RestoreOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ResumeOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\SendOfferForApprovalAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\WithdrawOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Template C (record view). One primary: sending (owner), approving (admin), resuming or restoring when it
 * applies, else Edit. Withdrawing and deleting (dangerous) sit in "⋮" with rejecting.
 */
class ViewOffer extends ViewRecord
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendOfferForApprovalAction::make()->color('primary'),
            ApproveOfferAction::make(primary: true),
            ApproveOfferWithGlassModelAction::make(primary: true),
            ResumeOfferAction::make(primary: true),
            RestoreOfferAction::make(primary: true),
            EditAction::make()
                ->visible(fn (OfferModel $record): bool => OfferResource::canEdit($record))
                ->color(fn (OfferModel $record): string => OfferActionVisibility::hasPrimaryAction($record) ? 'gray' : 'primary'),
            ActionGroup::make([
                RejectOfferAction::make(),
                WithdrawOfferAction::make(),
                DeleteOfferAction::make(),
                DeleteOfferPermanentlyAction::make(),
            ]),
        ];
    }
}
