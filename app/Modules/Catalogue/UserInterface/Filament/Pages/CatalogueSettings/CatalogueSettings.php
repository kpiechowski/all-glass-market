<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Pages\CatalogueSettings;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\SaveThumbnailSettings\SaveThumbnailSettingsCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ThumbnailMappingModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\ThumbnailMappingQueries;
use App\Modules\Catalogue\UserInterface\Filament\Pages\CatalogueSettings\Schemas\CatalogueSettingsForm;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Template D (settings), layout (a): one form of stacked sections, one sticky Zapisz. D-42: Admin
 * and Super Admin only. Saving sends one command and changes no Glass model (RULE-12).
 *
 * @property-read Schema $form
 */
class CatalogueSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $slug = 'catalogue-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('catalogue::pages.catalogue_settings.navigation_label');
    }

    public function getTitle(): string
    {
        return __('catalogue::pages.catalogue_settings.title');
    }

    public static function canAccess(): bool
    {
        return CurrentActor::can(CataloguePermission::Manage);
    }

    public function mount(): void
    {
        $data = ['positions' => [], 'colours' => [], 'equipment' => []];

        /** @var ThumbnailMappingModel $mapping */
        foreach (app(ThumbnailMappingQueries::class)->listing()->get() as $mapping) {
            $id = $mapping->dictionary_value_id;

            match ($mapping->dictionary) {
                Dictionary::Position => $data['positions'][$id] = ['shape' => $mapping->shape?->value, 'mirrored' => $mapping->mirrored],
                Dictionary::Colour => $data['colours'][$id] = ['custom' => true, 'fill' => $mapping->fill, 'opacity' => $mapping->opacity],
                Dictionary::Equipment => $data['equipment'][$id] = ['pictogram' => $mapping->pictogram?->value],
                default => null,
            };
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return CatalogueSettingsForm::configure($schema);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveAction()])
                        ->alignment($this->getFormActionsAlignment())
                        ->sticky()
                        ->key('form-actions'),
                ]),
        ]);
    }

    protected function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('catalogue::pages.catalogue_settings.actions.save'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function save(): void
    {
        /** @var array<string, array<int, array<string, mixed>>> $data */
        $data = $this->form->getState();

        $positions = [];
        foreach ($data['positions'] ?? [] as $id => $position) {
            $shape = $position['shape'] ?? null;
            $positions[(int) $id] = ['shape' => filled($shape) ? $shape : null, 'mirrored' => filled($shape) && (bool) ($position['mirrored'] ?? false)];
        }

        $colours = [];
        foreach ($data['colours'] ?? [] as $id => $colour) {
            $custom = (bool) ($colour['custom'] ?? false) && filled($colour['fill'] ?? null);
            $colours[(int) $id] = ['fill' => $custom ? $colour['fill'] : null, 'opacity' => $custom ? (int) $colour['opacity'] : null];
        }

        $equipment = [];
        foreach ($data['equipment'] ?? [] as $id => $item) {
            $equipment[(int) $id] = filled($item['pictogram'] ?? null) ? $item['pictogram'] : null;
        }

        CommandSender::send(new SaveThumbnailSettingsCommand(CurrentActor::get(), $positions, $colours, $equipment));

        Notification::make()->success()->title(__('catalogue::pages.catalogue_settings.saved'))->send();
    }
}
