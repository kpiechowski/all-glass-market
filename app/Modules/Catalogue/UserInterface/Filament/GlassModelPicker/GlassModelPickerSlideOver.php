<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;

/**
 * Turns any Filament action (a form button, a section header action, a table action or a bulk
 * action) into "pick a Glass model": the action opens the picker in a slide-over, the user marks a
 * card and confirms at the bottom, and the action's own `->action()` receives the id.
 *
 *     GlassModelPickerSlideOver::configure(
 *         Action::make('pickGlassModel')->label('Wybierz model szyby'),
 *         search: fn (): ?string => $hint,
 *     )->action(function (array $data): void {
 *         $glassModelId = (int) $data[GlassModelPickerSlideOver::STATE_KEY];
 *     });
 *
 * Bulk: `BulkAction::make(...)->action(fn (Collection $records, array $data) => ...)` after the same
 * `configure()`. A model already chosen is shown marked with `->fillForm([STATE_KEY => $id])`.
 * Whatever the action does with the id is the embedder's: through its own command, which must
 * check the model exists and is not withdrawn.
 */
final class GlassModelPickerSlideOver
{
    /** The key of the chosen id in the action's `$data`. */
    public const string STATE_KEY = 'glass_model_id';

    public static function configure(Action $action, string|Closure|null $search = null, PickerVariant|Closure|null $variant = null): Action
    {
        return $action
            ->slideOver()
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading(__('catalogue::resources.glass_model_picker.heading'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model_picker.confirm'))
            ->schema([
                GlassModelPickerField::make(self::STATE_KEY)
                    ->hiddenLabel()
                    ->required()
                    ->validationMessages(['required' => __('catalogue::resources.glass_model_picker.required')])
                    ->search($search)
                    ->variant($variant),
            ]);
    }
}
