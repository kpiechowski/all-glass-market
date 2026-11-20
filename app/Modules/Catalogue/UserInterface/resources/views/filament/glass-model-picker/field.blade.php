{{-- Glass model picker as a form field: the Livewire picker, its marked id bound to the field state (Modelable). --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <livewire:catalogue-glass-model-picker
        :variant="$getVariant()->value"
        :prefill="$getSearch()"
        wire:model="{{ $getStatePath() }}"
        :key="'catalogue-glass-model-picker-'.$getStatePath()"
    />
</x-dynamic-component>
