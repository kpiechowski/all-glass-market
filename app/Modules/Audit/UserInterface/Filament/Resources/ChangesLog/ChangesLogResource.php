<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Resources\ChangesLog;

use App\Modules\Audit\Domain\Models\ChangesLog;
use App\Modules\Audit\UserInterface\Filament\Resources\ChangesLog\Pages\ListChangesLogs;
use App\Modules\Audit\UserInterface\Filament\Resources\ChangesLog\Tables\ChangesLogTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChangesLogResource extends Resource
{
    protected static ?string $model = ChangesLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getModelLabel(): string
    {
        return __('audit::resource.changes_log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit::resource.changes_log.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ChangesLogTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangesLogs::route('/'),
        ];
    }
}
