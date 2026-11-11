{{-- Card of a Glass model (FR-04, D-53): App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables\GlassModelCardColumn. Styles: resources/css/admin/components/glass-card.css --}}
@php
    use App\Modules\Catalogue\Domain\Enums\MatchRoute;

    $record = $getRecord();
    $thumbnail = $getThumbnail();
    $offersLink = $getOffersLink();
    $route = MatchRoute::tryFrom((string) $record->getAttribute('match_route'));
    $furtherEurocodes = $record->eurocodes->where('is_main', false)->sortBy('code');
    $oemCodes = $record->oemCodes->sortBy('code');
    $parameters = array_filter([$record->position?->name, $record->colour?->name]);
@endphp

<div class="glass-card">
    @if ($thumbnail)
        <div class="glass-card__thumbnail">{{ $thumbnail }}</div>
    @endif

    <div class="glass-card__head">
        <span class="glass-card__main">{{ $record->mainEurocode?->code }}</span>

        <x-filament::badge :color="$record->withdrawn ? 'gray' : 'success'">
            {{ __('catalogue::resources.glass_model.state.' . ($record->withdrawn ? 'withdrawn' : 'active')) }}
        </x-filament::badge>

        @if ($route)
            <x-filament::badge color="gray" class="glass-card__match">
                {{ $route->getLabel() }}: {{ $record->getAttribute('matched_value') }}
            </x-filament::badge>
        @endif
    </div>

    <dl class="glass-card__facts">
        <div class="glass-card__fact">
            <dt>{{ __('catalogue::resources.glass_model.fields.further_eurocodes') }}</dt>
            <dd>
                @forelse ($furtherEurocodes as $eurocode)
                    <span class="glass-card__code">{{ $eurocode->code }}</span>
                @empty
                    —
                @endforelse
            </dd>
        </div>

        <div class="glass-card__fact">
            <dt>{{ __('catalogue::resources.glass_model.fields.oem_codes') }}</dt>
            <dd>
                @forelse ($oemCodes as $oemCode)
                    <span class="glass-card__code">{{ $oemCode->code }}</span>
                @empty
                    —
                @endforelse
            </dd>
        </div>

        <div class="glass-card__fact">
            <dt>{{ __('catalogue::resources.glass_model.card.parameters') }}</dt>
            <dd>
                {{ $parameters ? implode(' · ', $parameters) : '—' }}
                @if ($record->equipment->isNotEmpty())
                    <span class="glass-card__equipment">{{ $record->equipment->pluck('name')->sort()->implode(', ') }}</span>
                @endif
            </dd>
        </div>

        <div class="glass-card__fact">
            <dt>{{ __('catalogue::resources.glass_model.card.vehicles') }}</dt>
            <dd>{{ $record->vehicles_count }}</dd>
        </div>
    </dl>

    @if ($offersLink)
        <div class="glass-card__offers">{{ $offersLink }}</div>
    @endif
</div>
