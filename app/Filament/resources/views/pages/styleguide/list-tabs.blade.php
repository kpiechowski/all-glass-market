{{-- List tabs (template A): the main split of the data, never a filter. --}}
@php
    $page = $getLivewire();
@endphp

<x-filament::tabs label="Status użytkownika">
    @foreach ($page->listTabs() as $key => $label)
        <x-filament::tabs.item
            :active="$page->listTab === $key"
            wire:click="setListTab('{{ $key }}')"
        >
            {{ $label }}
        </x-filament::tabs.item>
    @endforeach
</x-filament::tabs>
