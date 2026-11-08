{{-- Composite person cell (D-13): App\Core\UserInterface\Filament\Tables\Columns\PersonColumn. Styles: resources/css/admin/components/person-cell.css, record-flag.css. --}}
@php
    $email = $getEmail();
    $flags = $getPersonFlags();
@endphp

<div class="person-cell">
    @if ($showsInitials())
        <span class="person-cell__initials" aria-hidden="true">{{ $getInitials() }}</span>
    @endif

    <div class="person-cell__text">
        <div class="person-cell__name-line">
            <span class="person-cell__name">{{ $getPersonName() }}</span>

            @foreach ($flags as $flag)
                <span @class(['record-flag', 'record-flag--' . $flag['color']])>
                    @if ($flag['icon'])
                        <x-filament::icon :icon="$flag['icon']" class="record-flag__icon" />
                    @endif
                    {{ $flag['label'] }}
                </span>
            @endforeach
        </div>

        @if (filled($email))
            <span class="person-cell__email">{{ $email }}</span>
        @endif
    </div>
</div>
