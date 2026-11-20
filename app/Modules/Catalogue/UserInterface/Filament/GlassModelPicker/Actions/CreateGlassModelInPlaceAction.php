<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\CreateGlassModel\CreateGlassModelCommand;
use App\Modules\Catalogue\Application\ModuleApi\EurocodeSuggestions;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPicker;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/**
 * FR-20, WF-01 from the picker: an admin creates a Glass model in place. The main Eurocode is
 * suggested from the search text (the Offers hint), normalised by RULE-03, for the admin to confirm
 * or correct; the create-glass-model command does the rest, and the picker marks the new model.
 * A taken Eurocode is refused by the entity and shown on the field, naming the model that holds it.
 */
final class CreateGlassModelInPlaceAction
{
    private const string TAKEN = 'catalogue::exceptions.glass_model.eurocode_is_taken';

    public static function make(): Action
    {
        return Action::make('createGlassModelInPlace')
            ->label(__('catalogue::resources.glass_model_picker.create.label'))
            ->icon(Heroicon::Plus)
            ->color('gray')
            ->modalHeading(__('catalogue::resources.glass_model_picker.create.heading'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model_picker.create.submit'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->fillForm(fn (GlassModelPicker $livewire): array => [
                'main_eurocode' => app(EurocodeSuggestions::class)->from(
                    filled($livewire->getTableSearch()) ? $livewire->getTableSearch() : $livewire->prefill,
                ),
            ])
            ->schema([
                TextInput::make('main_eurocode')
                    ->label(__('catalogue::resources.glass_model.fields.main_eurocode'))
                    ->helperText(__('catalogue::resources.glass_model.helpers.main_eurocode'))
                    ->required(),
                Select::make('position_id')
                    ->label(__('catalogue::resources.glass_model.fields.position'))
                    ->options(fn (): array => self::options(Dictionary::Position))
                    ->required()
                    ->native(false),
                Select::make('colour_id')
                    ->label(__('catalogue::resources.glass_model.fields.colour'))
                    ->options(fn (): array => self::options(Dictionary::Colour))
                    ->native(false),
            ])
            ->action(function (array $data, GlassModelPicker $livewire): void {
                $id = (int) CommandSender::send(new CreateGlassModelCommand(
                    actor: CurrentActor::get(),
                    mainEurocode: (string) $data['main_eurocode'],
                    positionId: (int) $data['position_id'],
                    colourId: filled($data['colour_id'] ?? null) ? (int) $data['colour_id'] : null,
                ), [self::TAKEN => ['mountedActions.0.data.main_eurocode']]);

                $livewire->markCreated($id, (string) GlassModelEurocodeModel::query()->where('glass_model_id', $id)->where('is_main', true)->value('code'));
            });
    }

    /** @return array<int, string> */
    private static function options(Dictionary $dictionary): array
    {
        return app(DictionaryValueQueries::class)->choosable($dictionary)->pluck('name', 'id')->all();
    }
}
