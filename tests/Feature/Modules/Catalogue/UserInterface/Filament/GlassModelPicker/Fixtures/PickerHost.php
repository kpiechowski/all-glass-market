<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\Fixtures;

use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPickerSlideOver;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

/**
 * Stands in for a module that embeds the picker (Offers): the demo of the seam. It knows nothing
 * of Catalogue but the picker's configurator and the data key.
 */
class PickerHost extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?int $chosen = null;

    public ?string $hint = null;

    public function pickAction(): Action
    {
        return GlassModelPickerSlideOver::configure(
            Action::make('pick'),
            search: fn (): ?string => $this->hint,
        )->action(function (array $data): void {
            $this->chosen = (int) $data[GlassModelPickerSlideOver::STATE_KEY];
        });
    }

    public function render(): string
    {
        return '<div>{{ $this->pickAction }}<x-filament-actions::modals /></div>';
    }
}
