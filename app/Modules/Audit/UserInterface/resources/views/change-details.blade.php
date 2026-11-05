<div class="space-y-4">
    <dl class="grid grid-cols-3 gap-2 text-sm">
        <dt class="font-medium">{{ __('audit::resources.record_history.fields.occurred_at') }}</dt>
        <dd class="col-span-2">{{ $entry->occurred_at->format('d.m.Y H:i') }}</dd>
        <dt class="font-medium">{{ __('audit::resources.record_history.fields.actor') }}</dt>
        <dd class="col-span-2">{{ $actor }}</dd>
        <dt class="font-medium">{{ __('audit::resources.record_history.fields.kind') }}</dt>
        <dd class="col-span-2">{{ __('audit::enums.change_kind.'.$entry->kind->value) }}</dd>
    </dl>

    @if ($entry->restored_from_at)
        <p class="text-sm italic">{{ __('audit::resources.restore.marker', ['date' => $entry->restored_from_at->format('d.m.Y H:i')]) }}</p>
    @endif

    @if ($lines !== [])
        <ul class="space-y-2 text-sm">
            @foreach ($lines as $line)
                <li>
                    <span class="font-medium">{{ $line->label }}:</span>
                    @if ($line->old !== null || $line->new !== null)
                        @include('audit::partials.value', ['value' => $line->old])
                        &rarr;
                        @include('audit::partials.value', ['value' => $line->new])
                    @else
                        @foreach ($line->added as $value)
                            + @include('audit::partials.value', ['value' => $value])
                        @endforeach
                        @foreach ($line->removed as $value)
                            &minus; @include('audit::partials.value', ['value' => $value])
                        @endforeach
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
