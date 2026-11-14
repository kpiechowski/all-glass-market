{{-- Catalogue row of a Glass model (FR-04, D-53): data in ($getRecord, $getThumbnail, $getOffersLink), HTML out, so the picker of 09 can render it outside the table. Styles: resources/css/admin/components/glass-card.css --}}
@php
    use App\Modules\Catalogue\Domain\Enums\MatchRoute;

    $record = $getRecord();
    $thumbnail = $getThumbnail();
    $offersLink = $getOffersLink();
    $route = MatchRoute::tryFrom((string) $record->getAttribute('match_route'));
    $furtherEurocodes = $record->eurocodes->where('is_main', false)->sortBy('code')->pluck('code');
    $oemCodes = $record->oemCodes->sortBy('code')->pluck('code');
    $vehicleNames = $record->vehicles->pluck('name')->values();
    $hiddenVehicles = $record->vehicles_count - min(3, $vehicleNames->count());
    $subtitle = array_filter([$record->position?->name, $record->colour?->name]);
    $equipment = $record->equipment->pluck('name')->sort()->values();
@endphp

<div class="glass-card">
    <div class="glass-card__picture">{{ $thumbnail }}</div>

    <div class="glass-card__body">
        <div class="glass-card__line glass-card__line--title">
            <span class="glass-card__code glass-card__code--main">{{ $record->mainEurocode?->code }}</span>

            <div class="glass-card__heading">
                <span class="glass-card__subtitle">{{ $subtitle ? implode(' · ', $subtitle) : '—' }}</span>

                @if ($record->withdrawn || $route)
                    <span class="glass-card__flags">
                        @if ($record->withdrawn)
                            <x-filament::badge color="gray">{{ __('catalogue::resources.glass_model.state.withdrawn') }}</x-filament::badge>
                        @endif

                        @if ($route)
                            <x-filament::badge color="gray" class="glass-card__match">
                                {{ $route->getLabel() }}: {{ $record->getAttribute('matched_value') }}
                            </x-filament::badge>
                        @endif
                    </span>
                @endif
            </div>
        </div>

        <dl class="glass-card__facts">
            <div class="glass-card__line">
                <dt>{{ __('catalogue::resources.glass_model.fields.further_eurocodes') }}</dt>
                <dd>@include('catalogue::filament.tables.glass-card-values', ['values' => $furtherEurocodes, 'limit' => 2, 'itemClass' => 'glass-card__code glass-card__code--further'])</dd>
            </div>

            <div class="glass-card__line">
                <dt>{{ __('catalogue::resources.glass_model.fields.oem_codes') }}</dt>
                <dd>@include('catalogue::filament.tables.glass-card-values', ['values' => $oemCodes, 'limit' => 2, 'itemClass' => 'glass-card__code glass-card__code--oem'])</dd>
            </div>

            <div class="glass-card__line">
                <dt>{{ __('catalogue::resources.glass_model.fields.equipment') }}</dt>
                <dd>@include('catalogue::filament.tables.glass-card-values', ['values' => $equipment, 'limit' => 3, 'itemClass' => 'glass-card__chip'])</dd>
            </div>
        </dl>
    </div>

    <div class="glass-card__side">
        <div class="glass-card__line glass-card__side-head">
            <span class="glass-card__side-label">{{ __('catalogue::resources.glass_model.card.vehicles') }} ({{ $record->vehicles_count }})</span>
            <span class="glass-card__offers">{{ $offersLink }}</span>
        </div>

        {{-- Always three lines, so the side keeps the main column's line grid. --}}
        <ul class="glass-card__vehicles">
            @foreach ([0, 1, 2] as $index)
                <li class="glass-card__line glass-card__vehicle">
                    @if ($vehicleNames->has($index))
                        <span class="glass-card__value">{{ $vehicleNames[$index] }}</span>
                    @elseif ($index === 0)
                        <span class="glass-card__empty">—</span>
                    @endif
                    @if ($index === 2 && $hiddenVehicles > 0)
                        <span class="glass-card__more" x-tooltip="{ content: @js($vehicleNames->slice(3)->implode(', ')) }">{{ __('catalogue::resources.glass_model.card.more', ['count' => $hiddenVehicles]) }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
