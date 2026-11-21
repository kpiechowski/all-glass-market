{{-- One equipment pictogram from the shipped set (FR-15). The markup is shipped code, never user input. --}}
@props(['pictogram'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="{{ \App\Modules\Catalogue\Domain\Enums\Pictogram::VIEW_BOX }}" class="glass-thumbnail__pictogram" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $pictogram->markup() !!}</svg>
