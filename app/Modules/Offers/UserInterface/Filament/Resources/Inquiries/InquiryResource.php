<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\Infrastructure\Persistence\Queries\InquiryQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Schemas\InquiryInfolist;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Tables\InquiriesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-22, FR-21a: "Zapytania". A seller sees the Inquiries on own Offers and answers them; an admin sees all of
 * them. Read-only list: the row opens the Inquiry, where it is marked read and answered. Listed in the "Oferty" group.
 */
class InquiryResource extends Resource
{
    protected static ?string $model = InquiryModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function getModelLabel(): string
    {
        return __('offers::resources.inquiry.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offers::resources.inquiry.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(InquiryQueries::class)->visibleTo((int) auth()->id(), CurrentActor::can(OffersPermission::ManageAll));
    }

    public static function canViewAny(): bool
    {
        return CurrentActor::can(OffersPermission::Create);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return InquiryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InquiriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'view' => ViewInquiry::route('/{record}'),
        ];
    }
}
