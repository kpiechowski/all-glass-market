{{-- FR-36, FR-37: twelve months of Offers added and sold. Styles: resources/css/admin/components/activity-chart.css --}}
<x-filament-widgets::widget>
    <section class="activity-chart" x-data="{ open: true, active: null }" x-on:mouseleave="active = null">
        <header class="activity-chart__head">
            <button type="button" class="activity-chart__toggle" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-label="{{ __('offers::widgets.activity_chart.toggle') }}">
                <x-filament::icon :icon="$chevron" class="activity-chart__chevron" x-bind:class="{ 'activity-chart__chevron--closed': ! open }" />
                <span class="activity-chart__title">{{ __('offers::widgets.activity_chart.title') }}</span>
                <span class="activity-chart__subtitle">{{ __('offers::widgets.activity_chart.subtitle') }}</span>
            </button>

            @if ($managesAll)
                <label class="activity-chart__filter">
                    <span class="activity-chart__filter-label">{{ __('offers::widgets.activity_chart.seller_filter') }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="sellerId">
                            <option value="">{{ __('offers::widgets.activity_chart.all_sellers') }}</option>
                            @foreach ($sellers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
            @endif
        </header>

        <div class="activity-chart__body" x-show="open">
            <ul class="activity-chart__legend">
                @foreach ($series as $key => $line)
                    <li class="activity-chart__legend-item">
                        <span class="activity-chart__key activity-chart__key--{{ $key }}"></span>{{ $line['label'] }}
                    </li>
                @endforeach
            </ul>

            <div class="activity-chart__plot" wire:loading.class="activity-chart__plot--loading">
                <svg class="activity-chart__svg" viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ __('offers::widgets.activity_chart.table_caption') }}">
                    @foreach ($ticks as $tick)
                        <line class="activity-chart__grid" x1="{{ $plotLeft }}" x2="{{ $plotRight }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" />
                        <text class="activity-chart__tick" x="{{ $plotLeft - 6 }}" y="{{ $tick['y'] + 4 }}" text-anchor="end">{{ $tick['value'] }}</text>
                    @endforeach

                    @foreach ($months as $month)
                        <text class="activity-chart__tick" x="{{ $month['x'] }}" y="{{ $plotBottom + 18 }}" text-anchor="middle">{{ $month['short'] }}</text>
                    @endforeach

                    @foreach ($months as $index => $month)
                        <line class="activity-chart__crosshair" x-show="active === {{ $index }}" x1="{{ $month['x'] }}" x2="{{ $month['x'] }}" y1="{{ $plotTop }}" y2="{{ $plotBottom }}" />
                    @endforeach

                    @foreach ($series as $key => $line)
                        <path class="activity-chart__line activity-chart__line--{{ $key }}" d="{{ $line['path'] }}" />
                        @foreach ($line['points'] as $point)
                            <circle class="activity-chart__dot activity-chart__dot--{{ $key }}" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" />
                        @endforeach
                    @endforeach

                    @foreach ($months as $index => $month)
                        <rect class="activity-chart__hit" x="{{ $month['x'] - $bandWidth / 2 }}" y="{{ $plotTop }}" width="{{ $bandWidth }}" height="{{ $plotBottom - $plotTop }}" tabindex="0"
                              x-on:mouseenter="active = {{ $index }}" x-on:focus="active = {{ $index }}" x-on:blur="active = null" />
                    @endforeach
                </svg>

                @foreach ($months as $index => $month)
                    <div class="activity-chart__tooltip" x-show="active === {{ $index }}" x-cloak style="left: {{ round($month['x'] / $width * 100, 2) }}%">
                        <span class="activity-chart__tooltip-month">{{ $month['long'] }}</span>
                        @foreach ($series as $key => $line)
                            <span class="activity-chart__tooltip-row">
                                <span class="activity-chart__key activity-chart__key--{{ $key }}"></span>
                                <strong>{{ $month[$key] }}</strong>
                                <span class="activity-chart__tooltip-name">{{ $line['label'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <table class="activity-chart__table">
                <caption>{{ __('offers::widgets.activity_chart.table_caption') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('offers::widgets.activity_chart.month_column') }}</th>
                        @foreach ($series as $line)
                            <th scope="col">{{ $line['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($months as $month)
                        <tr>
                            <th scope="row">{{ $month['long'] }}</th>
                            <td>{{ $month['added'] }}</td>
                            <td>{{ $month['sold'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-filament-widgets::widget>
