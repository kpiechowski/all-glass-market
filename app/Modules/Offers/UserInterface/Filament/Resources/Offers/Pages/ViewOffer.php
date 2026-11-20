<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferWithGlassModelAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\RejectOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\SendOfferForApprovalAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Template C (record view). One primary: sending (owner) or approving (admin) when it applies,
 * else Edit. Rejecting is a dangerous action and sits in "⋮".
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
            EditAction::make()
                ->color(fn (OfferModel $record): string => OfferActionVisibility::hasPrimaryAction($record) ? 'gray' : 'primary'),
            ActionGroup::make([
                RejectOfferAction::make(),
            ]),
        ];
    }
}
