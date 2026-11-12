{{-- At most $limit values of a fact, then "+N" with the rest on hover. $total overrides the count when only a few values were loaded. Styles: glass-card.css --}}
@php
    $total ??= $values->count();
    $shown = $values->take($limit);
    $hidden = $total - $shown->count();
    $rest = $values->slice($limit)->implode(', ');
@endphp

@forelse ($shown as $value)
    <span class="glass-card__value">{{ $value }}</span>
@empty
    —
@endforelse
@if ($hidden > 0)
    <span class="glass-card__more" @if ($rest !== '') x-tooltip="{ content: @js($rest) }" @endif>{{ __('catalogue::resources.glass_model.card.more', ['count' => $hidden]) }}</span>
@endif
