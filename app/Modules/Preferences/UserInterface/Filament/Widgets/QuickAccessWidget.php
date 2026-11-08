<?php

declare(strict_types=1);

namespace App\Modules\Preferences\UserInterface\Filament\Widgets;

use App\Core\Domain\QuickAccess\QuickAccessItem;
use App\Core\Domain\QuickAccess\QuickAccessItems;
use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Preferences\Application\Commands\SavePreference\SavePreferenceCommand;
use App\Modules\Preferences\Application\ModuleApi\QuickAccess;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/**
 * Template F (dashboard), issue 13: the user's own "Szybki dostęp" tiles. Each stored key is
 * resolved in the Core quick-access registry when drawn; an unknown key, or a place the user may
 * no longer open, is not drawn. "Edytuj" switches to edit mode: tiles rock gently and reorder by
 * drag and drop (Filament's own `x-sortable`, SortableJS), each gets a remove button, and the
 * "Dostępne" palette under the grid lists the places left to add, grouped as the registry groups
 * them. Every change is saved at once through SavePreferenceCommand.
 *
 * A stored key the user cannot open now (a role change) is kept in its place on every save and
 * still counts towards the limit, so the tile comes back when access does. Only keys no longer
 * registered are dropped.
 */
class QuickAccessWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static ?int $sort = -5;

    protected string $view = 'preferences::widgets.quick-access';

    protected int|string|array $columnSpan = 'full';

    public bool $isEditing = false;

    /** The tile added last, drawn with its entrance animation. */
    public ?string $justAdded = null;

    /** The palette item removed from the grid last, drawn with its entrance animation. */
    public ?string $justReturned = null;

    /** @var list<string>|null the stored keys, read once per request and dropped on save */
    private ?array $storedKeysCache = null;

    public function toggleEditAction(): Action
    {
        return Action::make('toggleEdit')
            ->label(fn (): string => __('preferences::widgets.quick_access.actions.'.($this->isEditing ? 'done' : 'edit')))
            ->icon(fn (): ?Heroicon => $this->isEditing ? Heroicon::Check : null)
            ->color(fn (): string => $this->isEditing ? 'primary' : 'gray')
            ->size('sm')
            ->visible(fn (): bool => $this->isEditing || $this->pinnedItems() !== [])
            ->action(function (): void {
                $this->isEditing = ! $this->isEditing;
                $this->justAdded = null;
                $this->justReturned = null;
            });
    }

    /** The empty state's button: opens edit mode with the palette. */
    public function startAddingAction(): Action
    {
        return Action::make('startAdding')
            ->label(__('preferences::widgets.quick_access.actions.add'))
            ->icon(Heroicon::Plus)
            ->color('primary')
            ->visible(fn (): bool => ! $this->isEditing && $this->availableItems() !== [])
            ->action(function (): void {
                $this->isEditing = true;
            });
    }

    /**
     * Called by the sortable grid with the drawn tiles in their new order. Anything but a
     * reordering of the drawn tiles is ignored, and the next render puts the grid back.
     *
     * @param  array<int, mixed>  $keys
     */
    public function reorder(array $keys): void
    {
        $drawn = $this->drawnKeys();
        $reordered = array_values(array_filter($keys, is_string(...)));

        if (count($reordered) !== count($drawn) || array_diff($drawn, $reordered) !== [] || array_diff($reordered, $drawn) !== []) {
            return;
        }

        $this->justAdded = null;
        $this->justReturned = null;

        $stored = [];

        foreach ($this->storedKeys() as $key) {
            $stored[] = in_array($key, $drawn, true) ? array_shift($reordered) : $key;
        }

        $this->persist($stored);
    }

    /** Pins a place from the palette at the end of the grid. */
    public function add(string $key): void
    {
        $available = array_map(fn (QuickAccessItem $item): string => $item->key, $this->availableItems());

        if (! in_array($key, $available, true) || $this->isFull()) {
            return;
        }

        if ($this->persist([...$this->storedKeys(), $key])) {
            $this->justAdded = $key;
            $this->justReturned = null;
        }
    }

    /** Unpins a drawn tile; it goes back to the palette. */
    public function remove(string $key): void
    {
        if (! in_array($key, $this->drawnKeys(), true)) {
            return;
        }

        if ($this->persist(array_values(array_diff($this->storedKeys(), [$key])))) {
            $this->justReturned = $key;
            $this->justAdded = null;
        }
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $registry = app(QuickAccessItems::class);
        $tiles = array_map(fn (QuickAccessItem $item): array => [
            'key' => $item->key,
            'label' => $item->label(),
            'icon' => $item->icon(),
            'url' => $item->url(),
        ], $this->pinnedItems());

        $palette = array_map(fn (array $entry): array => [
            'key' => $entry['group']->key,
            'label' => $entry['group']->label(),
            'icon' => $entry['group']->icon,
            'items' => array_map(fn (QuickAccessItem $item): array => [
                'key' => $item->key,
                'label' => $item->label(),
                'icon' => $item->icon(),
            ], $entry['items']),
        ], $this->isEditing ? $registry->inGroups($this->availableItems()) : []);

        return [
            'tiles' => $tiles,
            'palette' => $palette,
            'max' => QuickAccess::MAX_ITEMS,
            'stored' => count($this->storedKeys()),
            'isFull' => $this->isFull(),
        ];
    }

    /**
     * The stored keys that are still registered, in the user's order, including places the user
     * cannot open now. This is what a save writes back.
     *
     * @return list<string>
     */
    private function storedKeys(): array
    {
        $registry = app(QuickAccessItems::class);

        return $this->storedKeysCache ??= array_values(array_filter(
            app(QuickAccess::class)->keysFor(CurrentActor::get()->id),
            fn (string $key): bool => $registry->has($key),
        ));
    }

    /**
     * The tiles drawn: stored, registered and open to the user, in their order.
     *
     * @return list<QuickAccessItem>
     */
    private function pinnedItems(): array
    {
        $registry = app(QuickAccessItems::class);
        $items = [];

        foreach ($this->storedKeys() as $key) {
            $item = $registry->find($key);

            if ($item !== null && $item->isVisible()) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /** @return list<string> */
    private function drawnKeys(): array
    {
        return array_map(fn (QuickAccessItem $item): string => $item->key, $this->pinnedItems());
    }

    private function isFull(): bool
    {
        return count($this->storedKeys()) >= QuickAccess::MAX_ITEMS;
    }

    /**
     * Places the user may open and has not stored yet, in registration order.
     *
     * @return list<QuickAccessItem>
     */
    private function availableItems(): array
    {
        $stored = $this->storedKeys();

        return array_values(array_filter(
            app(QuickAccessItems::class)->visible(),
            fn (QuickAccessItem $item): bool => ! in_array($item->key, $stored, true),
        ));
    }

    /**
     * Saves the tiles. A refusal is shown as a notification by CommandSender; from a plain
     * Livewire call its halt is swallowed here, and the grid re-renders from what is stored.
     *
     * @param  list<string>  $keys
     */
    private function persist(array $keys): bool
    {
        $this->storedKeysCache = null;

        try {
            CommandSender::send(new SavePreferenceCommand(CurrentActor::get(), QuickAccess::PREFERENCE, $keys));
        } catch (Halt) {
            return false;
        }

        return true;
    }
}
