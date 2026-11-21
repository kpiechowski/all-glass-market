<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\InquiryResource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Template A (list): the row opens the Inquiry; nothing is changed from here. */
class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->recordUrl(fn (InquiryModel $record): string => InquiryResource::getUrl('view', ['record' => $record]))
            ->defaultSort('sent_at', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedChatBubbleLeftRight,
            heading: __('offers::resources.inquiry.empty.heading'),
            description: __('offers::resources.inquiry.empty.description'),
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (InquiryModel $record): string => $record->offer->number)
                ->subtitle(fn (InquiryModel $record): string => $record->note)
                ->status(fn (InquiryModel $record) => $record->state)
                ->meta(fn (InquiryModel $record): array => [$record->sender->name, $record->sent_at->format('d.m.Y H:i')]),
            TextColumn::make('offer.number')
                ->label(__('offers::resources.inquiry.fields.offer'))
                ->sortable()
                ->visibleFrom('md'),
            TextColumn::make('note')
                ->label(__('offers::resources.inquiry.fields.note'))
                ->limit(80)
                ->visibleFrom('md'),
            TextColumn::make('sender.name')
                ->label(__('offers::resources.inquiry.fields.sender'))
                ->visibleFrom('md'),
            TextColumn::make('state')
                ->label(__('offers::resources.inquiry.fields.state'))
                ->badge()
                ->visibleFrom('md'),
            TextColumn::make('sent_at')
                ->label(__('offers::resources.inquiry.fields.sent_at'))
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->visibleFrom('md'),
        ];
    }
}
