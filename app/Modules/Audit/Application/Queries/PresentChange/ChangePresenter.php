<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\PresentChange;

use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Audit\Domain\Contracts\ReferenceNames;
use App\Modules\Audit\Domain\Enums\ValuePresenterKind;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Turns an entry's raw values into lines of label, old and new, from the
 * registration as it is now (FR-08, D-35, D-40). A display read: the UI hands
 * it the entry's stored arrays, so no model crosses into Application.
 *
 * - a reference shows the record's current name; a soft-deleted one shows its
 *   name marked removed; one gone entirely shows its id marked removed;
 * - a field or relation the module no longer registers shows under its
 *   technical name with raw values.
 */
final class ChangePresenter
{
    public function __construct(
        private AuditableRecords $records,
        private ReferenceNames $references,
    ) {}

    /**
     * @param  string  $recordType  the entry's stored type key
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relationChanges
     * @return list<PresentedLine>
     */
    public function present(string $recordType, array $changes, array $relationChanges): array
    {
        $type = $this->records->findByKey($recordType);
        $lines = [];

        foreach ($changes as $field => $change) {
            $presenter = $type?->valuePresenters[$field] ?? ValuePresenter::plain();
            $lines[] = new PresentedLine(
                $type === null ? $field : $type->labelOf($field),
                $this->value($presenter, $change['old'] ?? null),
                $this->value($presenter, $change['new'] ?? null),
            );
        }

        foreach ($relationChanges as $relation => $change) {
            $tracked = $type?->trackedRelations[$relation] ?? null;
            $presenter = $tracked?->item ?? ValuePresenter::plain();

            $lines[] = new PresentedLine(
                $tracked === null ? $relation : __($tracked->label),
                added: array_map(fn (mixed $item): PresentedValue => $this->value($presenter, $item), $change['added'] ?? []),
                removed: array_map(fn (mixed $item): PresentedValue => $this->value($presenter, $item), $change['removed'] ?? []),
            );
        }

        return $lines;
    }

    private function value(ValuePresenter $presenter, mixed $raw): PresentedValue
    {
        if ($raw === null) {
            return new PresentedValue(__('audit::presentation.empty'));
        }

        return match ($presenter->kind) {
            ValuePresenterKind::Reference => $this->reference($presenter, $raw),
            ValuePresenterKind::Enum => new PresentedValue($this->enumLabel((string) $presenter->target, $raw)),
            ValuePresenterKind::Money => new PresentedValue($this->money($raw)),
            ValuePresenterKind::Date => new PresentedValue($this->date($raw)),
            ValuePresenterKind::List => new PresentedValue($this->list($raw)),
            ValuePresenterKind::Plain => new PresentedValue($this->plain($raw)),
        };
    }

    private function reference(ValuePresenter $presenter, mixed $raw): PresentedValue
    {
        if (! is_int($raw) && ! is_string($raw)) {
            return new PresentedValue($this->plain($raw));
        }

        $found = $this->references->find((string) $presenter->target, (string) $presenter->nameAttribute, $raw);

        return $found === null
            ? new PresentedValue((string) $raw, removed: true)
            : new PresentedValue($found['name'], $found['removed']);
    }

    private function enumLabel(string $enumClass, mixed $raw): string
    {
        if (! enum_exists($enumClass) || (! is_int($raw) && ! is_string($raw))) {
            return $this->plain($raw);
        }

        $case = $enumClass::tryFrom($raw);

        if ($case === null) {
            return $this->plain($raw);
        }

        return method_exists($case, 'getLabel') ? (string) $case->getLabel() : $case->name;
    }

    private function money(mixed $raw): string
    {
        if (! is_numeric($raw)) {
            return $this->plain($raw);
        }

        $amount = (float) $raw;
        $decimals = floor($amount) === $amount ? 0 : 2;

        return number_format($amount, $decimals, ',', ' ').' '.__('audit::presentation.currency');
    }

    private function date(mixed $raw): string
    {
        if (! is_string($raw)) {
            return $this->plain($raw);
        }

        try {
            $date = CarbonImmutable::parse($raw);
        } catch (Throwable) {
            return $raw;
        }

        return $date->format(strlen($raw) <= 10 ? 'd.m.Y' : 'd.m.Y H:i');
    }

    private function list(mixed $raw): string
    {
        if (! is_array($raw)) {
            return $this->plain($raw);
        }

        return implode(', ', array_map(fn (mixed $item): string => $this->plain($item), $raw));
    }

    private function plain(mixed $raw): string
    {
        return match (true) {
            is_bool($raw) => __($raw ? 'audit::presentation.yes' : 'audit::presentation.no'),
            is_scalar($raw) => (string) $raw,
            default => (string) json_encode($raw, JSON_UNESCAPED_UNICODE),
        };
    }
}
