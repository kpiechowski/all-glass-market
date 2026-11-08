<x-filament-panels::page>
    <x-filament::tabs :label="$this->getTitle()">
        @foreach ($this->getVisibleTabs() as $tab)
            <x-filament::tabs.item tag="a" :href="$this->getTabUrl($tab)" :active="$this->tab() === $tab">
                {{ $tab->label() }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
