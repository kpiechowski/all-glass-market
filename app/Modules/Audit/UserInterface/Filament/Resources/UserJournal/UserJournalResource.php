<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Resources\UserJournal;

use App\Modules\Audit\Domain\Models\UserJournal;
use App\Modules\Audit\UserInterface\Filament\Resources\UserJournal\Pages\ListUserJournals;
use App\Modules\Audit\UserInterface\Filament\Resources\UserJournal\Tables\UserJournalTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserJournalResource extends Resource
{
    protected static ?string $model = UserJournal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    public static function getModelLabel(): string
    {
        return __('audit::resource.user_journal.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit::resource.user_journal.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return UserJournalTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserJournals::route('/'),
        ];
    }
}
