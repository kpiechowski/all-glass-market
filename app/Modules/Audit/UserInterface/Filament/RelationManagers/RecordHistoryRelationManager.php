<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\RelationManagers;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Infrastructure\Persistence\Queries\ChangeQueries;
use App\Modules\Audit\UserInterface\Filament\Tables\RecordHistoryTable;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The "Historia zmian" tab (FR-09, FR-10). A hosting resource lists it in
 * `getRelations()` with its registered type key:
 *
 *     RecordHistoryRelationManager::make(['recordType' => 'users.user'])
 *
 * It has no Eloquent relationship: it reads the owner record's entries through
 * `ChangeQueries`, and it is read-only. Visible with the RecordHistory Permission.
 */
class RecordHistoryRelationManager extends RelationManager
{
    public string $recordType = '';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return is_subclass_of($pageClass, ViewRecord::class)
            && CurrentActor::can(AuditPermission::RecordHistory);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('audit::resources.record_history.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    /**
     * The owner's entries as a has-many scoped to the record type: Filament
     * reads a relationship, so the query comes from `ChangeQueries` wrapped in one.
     *
     * @return HasMany<Model, Model>
     */
    public function getRelationship(): HasMany
    {
        $owner = $this->getOwnerRecord();

        return new HasMany(
            app(ChangeQueries::class)->forRecord($this->recordType, (int) $owner->getKey()),
            $owner,
            'record_id',
            $owner->getKeyName(),
        );
    }

    public function table(Table $table): Table
    {
        return RecordHistoryTable::configure($table, $this->recordType, (int) $this->getOwnerRecord()->getKey());
    }
}
