{{-- The Glass model of an Offer as Catalogue's summary gives it (FR-15): current data, withdrawn models still shown. --}}
@php
    /** @var \App\Modules\Catalogue\Application\ModuleApi\GlassModelSummary|null $summary */
@endphp

@if ($summary === null)
    <p class="text-sm text-gray-500">{{ __('offers::resources.offer.glass_model.missing') }}</p>
@else
    <div class="flex flex-wrap items-start gap-4">
        <x-catalogue::glass-thumbnail :thumbnail="$summary->thumbnail" :pictograms="false" />

        <dl class="grid gap-1 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <dt class="sr-only">{{ __('offers::resources.offer.glass_model.eurocodes') }}</dt>
                <dd class="font-semibold">{{ implode(', ', $summary->eurocodes) }}</dd>
                @if ($summary->withdrawn)
                    <x-filament::badge color="gray">{{ __('offers::resources.offer.glass_model.withdrawn') }}</x-filament::badge>
                @endif
            </div>
            <div>
                <dt class="inline text-gray-500">{{ __('offers::resources.offer.glass_model.position') }}:</dt>
                <dd class="inline">{{ $summary->position ?? '—' }}@if ($summary->colour) · {{ $summary->colour }}@endif</dd>
            </div>
            <div>
                <dt class="inline text-gray-500">{{ __('offers::resources.offer.glass_model.oem_codes') }}:</dt>
                <dd class="inline">{{ $summary->oemCodes === [] ? '—' : implode(', ', $summary->oemCodes) }}</dd>
            </div>
            <div>
                <dt class="inline text-gray-500">{{ __('offers::resources.offer.glass_model.equipment') }}:</dt>
                <dd class="inline">{{ $summary->equipment === [] ? '—' : implode(', ', $summary->equipment) }}</dd>
            </div>
            <div>
                <dt class="inline text-gray-500">{{ __('offers::resources.offer.glass_model.vehicles') }}:</dt>
                <dd class="inline">{{ $summary->vehicles === [] ? '—' : implode(', ', $summary->vehicles) }}</dd>
            </div>
        </dl>
    </div>
@endif
