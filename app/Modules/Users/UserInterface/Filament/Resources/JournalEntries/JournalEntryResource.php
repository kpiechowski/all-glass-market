<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Queries\JournalQueries;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Pages\ViewJournalEntry;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Schemas\JournalEntryInfolist;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Tables\JournalEntriesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The signed-in User's own Journal (FR-30, FR-31, RULE-12). The resource query
 * is owner-scoped, so another User's entry cannot be listed, opened or acted on
 * even by guessing its address; the handlers check the owner again.
 */
class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntryModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $slug = 'journal';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('users::resources.journal_entry.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users::resources.journal_entry.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(JournalQueries::class)->ownedBy(CurrentActor::get()->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = app(JournalQueries::class)->unreadCountFor(CurrentActor::get()->id);

        return $unread > 0 ? (string) $unread : null;
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    /** Nobody creates or edits an entry by hand (FR-31). */
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
        return $record instanceof JournalEntryModel && $record->user_id === auth()->id();
    }

    public static function canDeleteAny(): bool
    {
        return auth()->check();
    }

    public static function infolist(Schema $schema): Schema
    {
        return JournalEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'view' => ViewJournalEntry::route('/{record}'),
        ];
    }
}
