<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\ModuleApi;

use App\Modules\Audit\Domain\Contracts\RestoresRecord;
use Closure;

/**
 * What a module declares about one auditable record type (FR-07, D-33).
 * Every field of the record is audited except `excludedFields`.
 *
 * @see AuditableRecords
 */
final readonly class AuditableRecordType
{
    /** Never audited, whatever the module says: the kind of change already tells created, deleted and brought back. */
    public const array TECHNICAL_FIELDS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @param  string  $key  stable name of the type, stored in every entry, for example `users.user`; never rename it
     * @param  class-string  $modelClass  the record's read-only model
     * @param  string  $name  the type's name in Polish, a translation key such as `offers::audit.type`
     * @param  Closure(object): string  $title  names one record, given its model
     * @param  Closure(object): ?string  $url  the record's page, given its model; null when it has none
     * @param  list<string>  $excludedFields  business fields never recorded, for example `password`
     * @param  array<string, string>  $fieldLabels  field => label (translation key or plain text)
     * @param  array<string, ValuePresenter>  $valuePresenters  field => presenter; others show plain
     * @param  array<string, TrackedRelation>  $trackedRelations  relation name => declaration
     * @param  list<string>  $restorableFields  fields Restore may write back
     * @param  RestoresRecord|null  $restorer  carries a Restore out as the module's own command; without it the type offers no Restore
     */
    public function __construct(
        public string $key,
        public string $modelClass,
        public string $name,
        public Closure $title,
        public Closure $url,
        public array $excludedFields = [],
        public array $fieldLabels = [],
        public array $valuePresenters = [],
        public array $trackedRelations = [],
        public array $restorableFields = [],
        public ?RestoresRecord $restorer = null,
    ) {}

    public function isAudited(string $field): bool
    {
        return ! in_array($field, self::TECHNICAL_FIELDS, true) && ! in_array($field, $this->excludedFields, true);
    }

    /** Whether the module offers Restore on this type at all (FR-13). */
    public function offersRestore(): bool
    {
        return $this->restorer !== null && $this->restorableFields !== [];
    }

    /**
     * The raw old values an entry's changes would write back: restorable and audited fields only (RULE-05).
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return array<string, mixed>
     */
    public function restorableValues(array $changes): array
    {
        $values = [];

        foreach ($changes as $field => $change) {
            if (in_array($field, $this->restorableFields, true) && $this->isAudited($field)) {
                $values[$field] = $change['old'];
            }
        }

        return $values;
    }

    public function isTracked(string $relation): bool
    {
        return isset($this->trackedRelations[$relation]);
    }

    public function titleOf(object $record): string
    {
        return ($this->title)($record);
    }

    public function urlOf(object $record): ?string
    {
        return ($this->url)($record);
    }

    public function labelOf(string $field): string
    {
        $label = $this->fieldLabels[$field] ?? null;

        return $label === null ? $field : __($label);
    }

    public function presenterOf(string $field): ValuePresenter
    {
        return $this->valuePresenters[$field] ?? ValuePresenter::plain();
    }

    public function typeName(): string
    {
        return __($this->name);
    }
}
