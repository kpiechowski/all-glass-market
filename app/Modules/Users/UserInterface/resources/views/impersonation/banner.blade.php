<div style="background:#b45309;color:#fff;padding:0.5rem 1rem;display:flex;gap:1rem;align-items:center;justify-content:center;font-size:0.875rem;">
    <span>{{ __('users::impersonation.banner', ['target' => $targetName, 'impersonator' => $impersonatorName]) }}</span>
    <button type="button" wire:click="leave" style="background:#fff;color:#b45309;border-radius:0.375rem;padding:0.25rem 0.75rem;font-weight:600;">
        {{ __('users::impersonation.return') }}
    </button>
</div>
