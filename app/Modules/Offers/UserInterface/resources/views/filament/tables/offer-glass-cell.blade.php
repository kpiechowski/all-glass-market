{{-- The glass of an Offer in a list row (FR-31): thumbnail, main Eurocode, position and colour, or the seller's hint. Styles: resources/css/admin/components/glass-cell.css --}}
@php
    /** @var \App\Modules\Catalogue\Application\ModuleApi\GlassModelSummary|null $summary */
    $summary = $getState();
    $record = $getRecord();
@endphp

<div class="glass-cell">
    @if ($summary !== null)
        <x-catalogue::glass-thumbnail :thumbnail="$summary->thumbnail" :pictograms="false" class="glass-cell__thumbnail" />
        <div class="glass-cell__text">
            <span class="glass-cell__code">{{ $summary->mainEurocode }}</span>
            <span class="glass-cell__detail">{{ $summary->position ?? '—' }}@if ($summary->colour) · {{ $summary->colour }}@endif</span>
        </div>
    @elseif ($record->identification_hint !== null)
        <div class="glass-cell__text">
            <span class="glass-cell__code">{{ $record->identification_hint }}</span>
            <span class="glass-cell__detail">{{ __('offers::resources.offer.list.no_glass_model') }}</span>
        </div>
    @else
        <span class="glass-cell__detail">—</span>
    @endif
</div>
