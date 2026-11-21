<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Schemas;

use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Layout (a): one sheet section with the Inquiry's data; the state is its badge. */
class InquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('offers::resources.inquiry.sections.inquiry'))
                ->extraAttributes(['class' => 'infolist--sheet'])
                ->columns(['default' => 1, 'xl' => 2])
                ->schema([
                    TextEntry::make('offer.number')
                        ->label(__('offers::resources.inquiry.fields.offer'))
                        ->url(fn (InquiryModel $record): string => OfferResource::getUrl('view', ['record' => $record->offer_id])),
                    TextEntry::make('state')->label(__('offers::resources.inquiry.fields.state'))->badge(),
                    TextEntry::make('sender.name')->label(__('offers::resources.inquiry.fields.sender')),
                    TextEntry::make('channel')->label(__('offers::resources.inquiry.fields.channel'))->placeholder('—'),
                    TextEntry::make('note')->label(__('offers::resources.inquiry.fields.note'))->columnSpanFull(),
                    TextEntry::make('sent_at')->label(__('offers::resources.inquiry.fields.sent_at'))->dateTime('d.m.Y H:i'),
                    TextEntry::make('read_at')->label(__('offers::resources.inquiry.fields.read_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
                    TextEntry::make('answered_at')->label(__('offers::resources.inquiry.fields.answered_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
                ]),
        ]);
    }
}
