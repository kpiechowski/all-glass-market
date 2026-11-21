<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Tables;

use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOffersBulkAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ApproveOfferWithGlassModelAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ChangeGlassModelAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ChangeGlassModelOffersBulkAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ChangePriceOffersBulkAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\DeleteOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\DeleteOfferPermanentlyAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\DeleteOffersBulkAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\DuplicateOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\RejectOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ReleaseReservationAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ReserveOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\RestoreOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ResumeOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\ReturnSaleAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\SellOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\SendInquiryAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\WithdrawOfferAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions\WithdrawOffersBulkAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Template A (list): the approval actions (FR-09, FR-11), the filters (FR-31) and the glass and buyer's price columns, the glass read in one batch per page. The same table serves "Moje oferty" and "Wszystkie oferty". */
class OffersTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(OffersTableColumns::make())
            ->filters(OffersTableFilters::make())
            ->recordUrl(fn (OfferModel $record): string => OfferResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    ApproveOfferAction::make(),
                    ApproveOfferWithGlassModelAction::make(),
                    RejectOfferAction::make(),
                    ReserveOfferAction::make(),
                    ReleaseReservationAction::make(),
                    SellOfferAction::make(),
                    SendInquiryAction::make(),
                    ReturnSaleAction::make(),
                    ChangeGlassModelAction::make(),
                    DuplicateOfferAction::make(),
                    ResumeOfferAction::make(),
                    WithdrawOfferAction::make(),
                    RestoreOfferAction::make(),
                    DeleteOfferAction::make(),
                    DeleteOfferPermanentlyAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ApproveOffersBulkAction::make(),
                    ChangePriceOffersBulkAction::make(),
                    ChangeGlassModelOffersBulkAction::make(),
                    WithdrawOffersBulkAction::make(),
                    DeleteOffersBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedTag,
            heading: __('offers::resources.offer.empty.heading'),
            description: __('offers::resources.offer.empty.description'),
            actions: [CreateAction::make()],
        );
    }
}
