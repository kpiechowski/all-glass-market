<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Actions;

use App\Core\Domain\Events\ChangeKind;
use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\Application\Commands\RestoreChange\RestoreChangeCommand;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\Queries\PresentChange\ChangePresenter;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * FR-13, FR-14: "Przywróć" on a zmieniono entry. Shown only for a holder of the
 * Permission, on a type that offers Restore, when the entry holds a restorable
 * field and the record still exists; the handler checks the Permission again.
 * The dialog lists the values to be written and compares nothing (D-36).
 * It overwrites and deletes nothing: grey, with only the confirmation button red (SKILL, Action hierarchy).
 */
final class RestoreChangeAction
{
    public static function make(): Action
    {
        return Action::make('restore')
            ->label(__('audit::resources.restore.label'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->requiresConfirmation()
            ->modalSubmitAction(fn (Action $action): Action => $action->color('danger'))
            ->modalHeading(__('audit::resources.restore.heading'))
            ->modalDescription(fn (ChangeModel $record): string => __('audit::resources.restore.description', ['values' => self::valuesText($record)]))
            ->modalSubmitActionLabel(__('audit::resources.restore.submit'))
            ->visible(fn (ChangeModel $record): bool => self::isOffered($record))
            ->action(function (ChangeModel $record): void {
                CommandSender::send(new RestoreChangeCommand(CurrentActor::get(), (int) $record->getKey()));

                Notification::make()->success()->title(__('audit::resources.restore.done'))->send();
            });
    }

    public static function isOffered(ChangeModel $entry): bool
    {
        if ($entry->kind !== ChangeKind::Changed || ! CurrentActor::can(AuditPermission::RestoreChanges)) {
            return false;
        }

        $type = app(AuditableRecords::class)->findByKey($entry->record_type);

        return $type !== null
            && $type->offersRestore()
            && $type->restorableValues($entry->changes ?? []) !== []
            && $type->modelClass::query()->whereKey($entry->record_id)->exists();
    }

    /** "status → Aktywna, cena → 1 200 zł": each field with the old value that will be written. */
    private static function valuesText(ChangeModel $entry): string
    {
        $type = app(AuditableRecords::class)->findByKey($entry->record_type);
        $restorable = array_intersect_key($entry->changes ?? [], $type?->restorableValues($entry->changes ?? []) ?? []);
        $lines = app(ChangePresenter::class)->present($entry->record_type, $restorable, []);

        return implode(', ', array_map(
            fn ($line): string => $line->label.' → '.$line->old?->text.($line->old?->removed ? ' ('.__('audit::presentation.removed').')' : ''),
            $lines,
        ));
    }
}
