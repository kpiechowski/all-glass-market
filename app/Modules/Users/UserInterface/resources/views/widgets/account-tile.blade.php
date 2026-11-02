<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <x-filament-panels::avatar.user size="lg" :user="$user" />

        <div class="fi-account-widget-main">
            <h2 class="fi-account-widget-heading">{{ __('users::widgets.account_tile.user') }}</h2>
            <p class="fi-account-widget-user-name">{{ $user->name }}</p>
            <p class="fi-account-widget-user-name">{{ $accountTypeLabel }}</p>
            <a href="{{ $profileUrl }}" class="fi-link">{{ __('users::widgets.account_tile.edit_profile') }}</a>
        </div>

        <form action="{{ filament()->getLogoutUrl() }}" method="post" class="fi-account-widget-logout-form">
            @csrf
            <x-filament::button color="gray" tag="button" type="submit">
                {{ __('users::widgets.account_tile.sign_out') }}
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
