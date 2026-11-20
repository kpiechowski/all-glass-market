<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPickerSlideOver;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Component;

/**
 * FR-02, template E (slide-over): "Wybierz model szyby" opens Catalogue's picker, prefilled from
 * the hint typed so far and with the current model marked. Choosing a model drops the hint
 * (RULE-02); the id is only client state, the command checks it again.
 */
final class PickGlassModelAction
{
    public static function make(): Action
    {
        return GlassModelPickerSlideOver::configure(
            Action::make('pickGlassModel')
                ->label(__('offers::resources.offer.actions.pick_glass_model'))
                ->color('gray'),
            search: fn (Component $livewire): ?string => data_get($livewire, 'data.identification_hint'),
        )
            ->fillForm(fn (Get $get): array => [GlassModelPickerSlideOver::STATE_KEY => $get('glass_model_id')])
            ->action(function (array $data, Set $set): void {
                $set('glass_model_id', (int) $data[GlassModelPickerSlideOver::STATE_KEY]);
                $set('identification_hint', null);
                $set('unknown_model', false);
            });
    }
}
