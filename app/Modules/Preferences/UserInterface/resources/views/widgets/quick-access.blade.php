{{-- Issue 13, template F. Look and motion: resources/css/admin/components/quick-access.css. --}}
@php
    $id = $this->getId();
@endphp

<x-filament-widgets::widget class="quick-access-widget">
    <x-filament::section :heading="__('preferences::widgets.quick_access.heading')">
        <x-slot name="afterHeader">
            {{ $this->toggleEditAction }}
        </x-slot>

        @if ($tiles === [] && ! $isEditing)
            <div class="quick-access__empty">
                <div class="quick-access__empty-icon">
                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedSquares2x2" />
                </div>
                <p class="quick-access__empty-heading">{{ __('preferences::widgets.quick_access.empty.heading') }}</p>
                <p class="quick-access__empty-description">{{ __('preferences::widgets.quick_access.empty.description') }}</p>
                <div class="quick-access__empty-action">
                    {{ $this->startAddingAction }}
                </div>
            </div>
        @elseif ($isEditing)
            <div class="quick-access quick-access--editing">
                <div class="quick-access__edit-bar">
                    <span>{{ __('preferences::widgets.quick_access.edit_hint') }}</span>
                    <span class="quick-access__count">{{ __('preferences::widgets.quick_access.count', ['count' => $stored, 'max' => $max]) }}</span>
                </div>

                @if ($tiles === [])
                    <p class="quick-access__grid-empty">{{ __('preferences::widgets.quick_access.grid_empty') }}</p>
                @else
                    <ul
                        class="quick-access__grid quick-access__grid--editing"
                        x-sortable
                        data-sortable-animation-duration="200"
                        x-on:end.stop="$wire.reorder($el.sortable.toArray())"
                        role="list"
                    >
                        @foreach ($tiles as $tile)
                            <li
                                wire:key="quick-access-edit-{{ $tile['key'] }}"
                                x-sortable-item="{{ $tile['key'] }}"
                                x-sortable-handle
                                @class([
                                    'quick-access__slot',
                                    'quick-access__slot--entering' => $tile['key'] === $justAdded,
                                ])
                            >
                                <div class="quick-access__tile quick-access__tile--editing">
                                    <span class="quick-access__icon">
                                        <x-filament::icon :icon="$tile['icon']" />
                                    </span>
                                    <span class="quick-access__label">{{ $tile['label'] }}</span>

                                    <button
                                        type="button"
                                        class="quick-access__remove"
                                        x-on:pointerdown.stop
                                        x-on:mousedown.stop
                                        x-on:click="$el.closest('li').classList.add('quick-access__slot--leaving'); $wire.remove(@js($tile['key']))"
                                        wire:loading.attr="disabled"
                                        title="{{ __('preferences::widgets.quick_access.remove', ['label' => $tile['label']]) }}"
                                        aria-label="{{ __('preferences::widgets.quick_access.remove', ['label' => $tile['label']]) }}"
                                    >
                                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::XMark" />
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <section
                    @class(['quick-access__palette', 'quick-access__palette--full' => $isFull && $palette !== []])
                    aria-labelledby="{{ $id }}-palette"
                >
                    <div class="quick-access__palette-inner">
                        <div class="quick-access__palette-header">
                            <h3 id="{{ $id }}-palette" class="quick-access__palette-heading">{{ __('preferences::widgets.quick_access.palette.heading') }}</h3>

                            @if ($palette !== [] && ! $isFull)
                                <span class="quick-access__palette-hint">{{ __('preferences::widgets.quick_access.palette.hint') }}</span>
                            @endif
                        </div>

                        @if ($palette === [])
                            <p class="quick-access__palette-empty">{{ __('preferences::widgets.quick_access.palette.nothing_left') }}</p>
                        @else
                            @if ($isFull)
                                <p class="quick-access__palette-limit" role="status">
                                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedInformationCircle" />
                                    <span>{{ __('preferences::widgets.quick_access.palette.full', ['max' => $max]) }}</span>
                                </p>
                            @endif

                            <div class="quick-access__groups">
                                @foreach ($palette as $group)
                                    <div class="quick-access__group" role="group" aria-labelledby="{{ $id }}-group-{{ $group['key'] }}" wire:key="quick-access-group-{{ $group['key'] }}">
                                        <div class="quick-access__group-header">
                                            <x-filament::icon :icon="$group['icon']" class="quick-access__group-icon" />
                                            <span id="{{ $id }}-group-{{ $group['key'] }}" class="quick-access__group-label">{{ $group['label'] }}</span>
                                            <span
                                                class="quick-access__group-count"
                                                title="{{ trans_choice('preferences::widgets.quick_access.palette.group_count', count($group['items'])) }}"
                                            >{{ count($group['items']) }}</span>
                                        </div>

                                        <ul class="quick-access__minis" role="list">
                                            @foreach ($group['items'] as $item)
                                                <li
                                                    wire:key="quick-access-palette-{{ $item['key'] }}"
                                                    @class([
                                                        'quick-access__mini-slot',
                                                        'quick-access__mini-slot--entering' => $item['key'] === $justReturned,
                                                    ])
                                                >
                                                    <button
                                                        type="button"
                                                        class="quick-access__mini"
                                                        x-on:click="$el.parentElement.classList.add('quick-access__mini-slot--leaving'); $wire.add(@js($item['key']))"
                                                        wire:loading.attr="disabled"
                                                        @disabled($isFull)
                                                        title="{{ __('preferences::widgets.quick_access.palette.add', ['label' => $item['label']]) }}"
                                                        aria-label="{{ __('preferences::widgets.quick_access.palette.add', ['label' => $item['label']]) }}"
                                                    >
                                                        <span class="quick-access__mini-icon">
                                                            <x-filament::icon :icon="$item['icon']" />
                                                        </span>
                                                        <span class="quick-access__mini-label">{{ $item['label'] }}</span>
                                                        <span class="quick-access__mini-plus" aria-hidden="true">
                                                            <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::Plus" />
                                                        </span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        @else
            <div class="quick-access">
                <ul class="quick-access__grid" role="list">
                    @foreach ($tiles as $tile)
                        <li wire:key="quick-access-{{ $tile['key'] }}" class="quick-access__slot">
                            <a href="{{ $tile['url'] }}" class="quick-access__tile">
                                <span class="quick-access__icon">
                                    <x-filament::icon :icon="$tile['icon']" />
                                </span>
                                <span class="quick-access__label">{{ $tile['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
