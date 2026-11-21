{{-- FR-32: the seller's counts above "Moje oferty". Styles: resources/css/admin/components/stat-card.css --}}
<x-filament-widgets::widget>
    <div class="stat-cards">
        @foreach ($cards as $card)
            <a href="{{ $card['url'] }}" class="stat-card stat-card--{{ $card['variant'] }}">
                <span class="stat-card__icon">
                    <x-filament::icon :icon="$card['icon']" class="stat-card__glyph" />
                </span>
                <span class="stat-card__body">
                    <span class="stat-card__value">{{ $card['value'] }}</span>
                    <span class="stat-card__label">{{ $card['label'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
