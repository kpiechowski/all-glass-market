{{-- Mobile card (template A): the one cell shown below `md`. Styles: resources/css/admin/components/list-card.css. --}}
@php
    use App\Core\UserInterface\Filament\Pages\Styleguide\Tables\DemoUsers;

    $record = $getRecord();
    $status = DemoUsers::STATUSES[$record['status']];
@endphp

<div class="list-card">
    <div class="list-card__head">
        <span class="list-card__title">
            {{ $record['name'] }}
            @if ($record['must_change_password'])
                <x-filament::icon icon="heroicon-o-key" label="Wymaga zmiany hasła" class="list-card__flag" />
            @endif
        </span>
        <x-filament::badge :color="$status['color']">{{ $status['label'] }}</x-filament::badge>
    </div>
    <span class="list-card__meta">{{ $record['email'] }}</span>
    <div class="list-card__tags">
        <x-filament::badge color="gray">{{ DemoUsers::ACCOUNT_TYPES[$record['account_type']] }}</x-filament::badge>
        <x-filament::badge color="gray">{{ DemoUsers::ROLES[$record['role']] }}</x-filament::badge>
        <span class="list-card__note">od {{ \Illuminate\Support\Carbon::parse($record['joined_at'])->format('d.m.Y') }}</span>
    </div>
</div>
