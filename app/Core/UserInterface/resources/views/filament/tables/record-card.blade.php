{{-- Mobile card of a list row (D-12): App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn. Styles: resources/css/admin/components/list-card.css, record-flag.css; the card row: filament/tables/mobile.css. --}}
@php
    $title = $getCardTitle();
    $subtitle = $getCardSubtitle();
    $status = $getCardStatus();
    $badges = $getCardBadges();
    $flags = $getCardFlags();
    $meta = $getCardMeta();
@endphp

<div class="list-card">
    <div class="list-card__head">
        <span class="list-card__title">{{ $title }}</span>

        @if ($status)
            <x-filament::badge :color="$status['color']" :icon="$status['icon']" class="list-card__status">
                {{ $status['label'] }}
            </x-filament::badge>
        @endif
    </div>

    @if (filled($subtitle))
        <span class="list-card__subtitle">{{ $subtitle }}</span>
    @endif

    @if (count($flags) || count($badges) || count($meta))
        <div class="list-card__foot">
            @foreach ($flags as $flag)
                <span @class(['record-flag', 'record-flag--' . $flag['color']])>
                    @if ($flag['icon'])
                        <x-filament::icon :icon="$flag['icon']" class="record-flag__icon" />
                    @endif
                    {{ $flag['label'] }}
                </span>
            @endforeach

            @foreach ($badges as $badge)
                <x-filament::badge :color="$badge['color']" :icon="$badge['icon']">{{ $badge['label'] }}</x-filament::badge>
            @endforeach

            @foreach ($meta as $line)
                <span class="list-card__meta">{{ $line }}</span>
            @endforeach
        </div>
    @endif
</div>
