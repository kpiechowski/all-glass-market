<div class="space-y-4">
    <dl class="grid grid-cols-3 gap-2 text-sm">
        <dt class="font-medium">{{ __('audit::pages.technical_log.failed_jobs.fields.name') }}</dt>
        <dd class="col-span-2">{{ $job['job_class'] }}</dd>
        <dt class="font-medium">{{ __('audit::pages.technical_log.failed_jobs.fields.queue') }}</dt>
        <dd class="col-span-2">{{ $job['queue'] }}</dd>
        <dt class="font-medium">{{ __('audit::pages.technical_log.fields.time') }}</dt>
        <dd class="col-span-2">{{ $job['time'] }}</dd>
    </dl>

    <div>
        <p class="text-sm font-medium">{{ __('audit::pages.technical_log.failed_jobs.fields.exception') }}</p>
        <pre class="mt-1 max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs">{{ $job['exception'] }}</pre>
    </div>
</div>
