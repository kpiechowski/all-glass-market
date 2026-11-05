{{-- Mobile card (template A): the one cell shown below `md`. Inline styles: Core views are not a Tailwind source yet. --}}
@php
    use App\Core\UserInterface\Filament\Pages\Styleguide\Tables\DemoUsers;

    $record = $getRecord();
    $status = DemoUsers::STATUSES[$record['status']];
@endphp

<div style="display: flex; flex-direction: column; gap: 0.375rem; padding: 0.75rem 1rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
        <span style="font-weight: 600;">
            {{ $record['name'] }}
            @if ($record['must_change_password'])
                <x-filament::icon icon="heroicon-o-key" label="Wymaga zmiany hasła" style="display: inline; width: 1rem; height: 1rem; color: var(--warning-600);" />
            @endif
        </span>
        <x-filament::badge :color="$status['color']">{{ $status['label'] }}</x-filament::badge>
    </div>
    <span style="font-size: 0.875rem; color: var(--gray-500);">{{ $record['email'] }}</span>
    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
        <x-filament::badge color="gray">{{ DemoUsers::ACCOUNT_TYPES[$record['account_type']] }}</x-filament::badge>
        <x-filament::badge color="gray">{{ DemoUsers::ROLES[$record['role']] }}</x-filament::badge>
        <span style="font-size: 0.75rem; color: var(--gray-500); align-self: center;">od {{ \Illuminate\Support\Carbon::parse($record['joined_at'])->format('d.m.Y') }}</span>
    </div>
</div>
