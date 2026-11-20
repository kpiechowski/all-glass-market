{{-- The photos of an Offer in their order (FR-05); the first public photo is the main one. --}}
@php
    /** @var list<string> $urls */
    /** @var bool $marksMain */
@endphp

@if ($urls === [])
    <p class="text-sm text-gray-500">—</p>
@else
    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($urls as $url)
            <li class="relative">
                <img src="{{ $url }}" alt="" loading="lazy" class="aspect-square w-full rounded-lg object-cover">
                @if ($marksMain && $loop->first)
                    <x-filament::badge color="gray" class="absolute start-2 top-2">{{ __('offers::resources.offer.fields.main_photo') }}</x-filament::badge>
                @endif
            </li>
        @endforeach
    </ul>
@endif
