{{-- Catalogue row of a Glass model (FR-04, D-53): data in ($getRecord, $getThumbnail, $getOffersLink), HTML out, so the picker of 09 can render it outside the table. Styles: resources/css/admin/components/glass-card.css --}}
@php
    use App\Modules\Catalogue\Domain\Enums\MatchRoute;

    $record = $getRecord();
    $thumbnail = $getThumbnail();
    $offersLink = $getOffersLink();
    $route = MatchRoute::tryFrom((string) $record->getAttribute('match_route'));
    $furtherEurocodes = $record->eurocodes->where('is_main', false)->sortBy('code')->pluck('code');
    $oemCodes = $record->oemCodes->sortBy('code')->pluck('code');
    $vehicleNames = $record->vehicles->pluck('name');
    $subtitle = array_filter([$record->position?->name, $record->colour?->name]);
    $equipment = $record->equipment->pluck('name')->sort()->values();
@endphp

<div class="glass-card">
    <div class="glass-card__picture">{{ $thumbnail }}</div>

    <div class="glass-card__title">
        <div class="glass-card__heading">
            <span class="glass-card__main">{{ $record->mainEurocode?->code }}</span>
            <span class="glass-card__subtitle">{{ $subtitle ? implode(' · ', $subtitle) : '—' }}</span>
        </div>

        <div class="glass-card__flags">
            @if ($record->withdrawn)
                <x-filament::badge color="gray">{{ __('catalogue::resources.glass_model.state.withdrawn') }}</x-filament::badge>
            @endif

            @if ($route)
                <x-filament::badge color="gray" class="glass-card__match">
                    {{ $route->getLabel() }}: {{ $record->getAttribute('matched_value') }}
                </x-filament::badge>
            @endif
        </div>
    </div>

    <dl class="glass-card__facts">
        <div class="glass-card__fact">
            <dt>{{ __('catalogue::resources.glass_model.fields.further_eurocodes') }}</dt>
            <dd>@include('catalogue::filament.tables.glass-card-values', ['values' => $furtherEurocodes, 'limit' => 2])</dd>
        </div>

        <div class="glass-card__fact">
            <dt>{{ __('catalogue::resources.glass_model.fields.oem_codes') }}</dt>
            <dd>@include('catalogue::filament.tables.glass-card-values', ['values' => $oemCodes, 'limit' => 2])</dd>
        </div>

        <div class="glass-card__fact">
            <dt>{{ __('catalogue::resources.glass_model.card.vehicles') }} ({{ $record->vehicles_count }})</dt>
            <dd>@include('catalogue::filament.tables.glass-card-values', ['values' => $vehicleNames, 'limit' => 2, 'total' => $record->vehicles_count])</dd>
        </div>
    </dl>

    <div class="glass-card__footer">
        <ul class="glass-card__chips">
            @foreach ($equipment->take(4) as $name)
                <li class="glass-card__chip">{{ $name }}</li>
            @endforeach
            @if ($equipment->count() > 4)
                <li class="glass-card__chip" x-tooltip="{ content: @js($equipment->slice(4)->implode(', ')) }">{{ __('catalogue::resources.glass_model.card.more', ['count' => $equipment->count() - 4]) }}</li>
            @endif
        </ul>

        <div class="glass-card__offers">{{ $offersLink }}</div>
    </div>
</div>
