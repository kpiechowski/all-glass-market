<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\ModuleApi;

/**
 * Where a module declares which of its records are auditable, in its
 * provider's boot() (FR-07, D-33):
 *
 *     $records->register(new AuditableRecordType(
 *         key: 'offers.offer',                         // stable, stored in every entry: never rename it
 *         modelClass: OfferModel::class,
 *         name: 'offers::audit.type',
 *         title: fn (OfferModel $offer): string => $offer->title,
 *         url: fn (OfferModel $offer): ?string => OfferResource::getUrl('view', ['record' => $offer]),
 *         excludedFields: ['synced_at'],    // business fields only; id and the timestamps are always ignored
 *         fieldLabels: ['price' => 'offers::audit.fields.price', 'status' => 'offers::audit.fields.status'],
 *         valuePresenters: [
 *             'producer_id' => ValuePresenter::reference(ProducerModel::class),
 *             'status' => ValuePresenter::enum(OfferStatus::class),
 *             'price' => ValuePresenter::money(),
 *         ],
 *         trackedRelations: ['eurocodes' => new TrackedRelation('offers::audit.relations.eurocodes', ValuePresenter::reference(EurocodeModel::class, 'code'))],
 *         restorableFields: ['price', 'status'],
 *         restorer: new RestoreOffer(app(CommandBus::class)),   // implements RestoresRecord; sends the module's own update command
 *     ));
 *
 * From then on every creation, change, deletion and return from deletion of
 * the model, from any path, becomes one Change entry. A type nobody registered
 * is ignored. The record's `id`, `created_at`, `updated_at` and `deleted_at` are never
 * audited, so a save that touches only a timestamp leaves no entry. An audited relation must be synced through
 * `EloquentRepository::syncRelation()`, or its changes cannot be seen.
 * Registration lives in memory: a module that is not installed registers
 * nothing. Writing entries is Audit's own business: there is no API for it.
 */
final class AuditableRecords
{
    /** @var array<class-string, AuditableRecordType> */
    private array $types = [];

    /** @var array<string, AuditableRecordType> */
    private array $byKey = [];

    public function register(AuditableRecordType $type): void
    {
        $this->types[$type->modelClass] = $type;
        $this->byKey[$type->key] = $type;
    }

    public function find(string $modelClass): ?AuditableRecordType
    {
        return $this->types[$modelClass] ?? null;
    }

    /** The registration behind a stored entry's type key. */
    public function findByKey(string $key): ?AuditableRecordType
    {
        return $this->byKey[$key] ?? null;
    }

    public function isRegistered(string $modelClass): bool
    {
        return isset($this->types[$modelClass]);
    }

    /** @return list<AuditableRecordType> */
    public function all(): array
    {
        return array_values($this->types);
    }
}
