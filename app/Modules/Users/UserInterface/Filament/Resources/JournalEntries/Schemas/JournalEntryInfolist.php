<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Schemas;

use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support\JournalKindLabel;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class JournalEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title')
                ->label(__('users::resources.journal_entry.fields.title')),

            TextEntry::make('severity')
                ->label(__('users::resources.journal_entry.fields.severity'))
                ->badge(),

            TextEntry::make('kind')
                ->label(__('users::resources.journal_entry.fields.kind'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => JournalKindLabel::for($state)),

            TextEntry::make('created_at')
                ->label(__('users::resources.journal_entry.fields.created_at'))
                ->dateTime(),

            // FR-33: plain text, escaped; only the line breaks are kept.
            TextEntry::make('content')
                ->label(__('users::resources.journal_entry.fields.content'))
                ->columnSpanFull()
                ->extraAttributes(['class' => 'whitespace-pre-line']),
        ]);
    }
}
