<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item tag="a" :href="static::getUrl()" :active="! $this->isFailedJobsTab()">
            {{ __('audit::pages.technical_log.tabs.files') }}
        </x-filament::tabs.item>
        <x-filament::tabs.item tag="a" :href="static::getUrl(['activeTab' => 'failed'])" :active="$this->isFailedJobsTab()">
            {{ __('audit::pages.technical_log.tabs.failed_jobs') }}
        </x-filament::tabs.item>
    </x-filament::tabs>

    @unless ($this->isFailedJobsTab())
        {{ $this->form }}
    @endunless

    {{ $this->table }}
</x-filament-panels::page>
