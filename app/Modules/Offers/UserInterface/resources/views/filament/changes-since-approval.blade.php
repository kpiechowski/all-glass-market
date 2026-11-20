{{-- FR-12: the Change history after the last approval, as Audit presents it: each field with old and new value. --}}
@php
    /** @var list<\App\Modules\Audit\Application\ModuleApi\PresentedLine> $lines */
@endphp

@if ($lines === [])
    <p class="text-sm text-gray-500">{{ __('offers::resources.offer.changes_since_approval.none') }}</p>
@else
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
