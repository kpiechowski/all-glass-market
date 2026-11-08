<div class="space-y-4">
    <dl class="grid grid-cols-3 gap-2 text-sm">
        <dt class="font-medium">{{ __('audit::pages.history_and_logs.technical_logs.fields.time') }}</dt>
        <dd class="col-span-2">{{ \Illuminate\Support\Carbon::parse($entry['time'])->format('d.m.Y H:i:s') }}</dd>
        <dt class="font-medium">{{ __('audit::pages.history_and_logs.technical_logs.fields.level') }}</dt>
        <dd class="col-span-2">{{ $levelLabel }}</dd>
    </dl>

    <div>
        <p class="text-sm font-medium">{{ __('audit::pages.history_and_logs.technical_logs.fields.message') }}</p>
        <pre class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $entry['message'] }}</pre>
    </div>

    @if ($entry['context'] !== '')
        <div>
            <p class="text-sm font-medium">{{ __('audit::pages.history_and_logs.technical_logs.fields.context') }}</p>
            <pre class="mt-1 whitespace-pre-wrap break-words text-xs">{{ $entry['context'] }}</pre>
        </div>
    @endif

    @if ($entry['stack_trace'] !== '')
        <div>
            <p class="text-sm font-medium">{{ __('audit::pages.history_and_logs.technical_logs.fields.stack_trace') }}</p>
            <pre class="mt-1 max-h-96 overflow-auto whitespace-pre text-xs">{{ $entry['stack_trace'] }}</pre>
        </div>
    @endif
</div>
