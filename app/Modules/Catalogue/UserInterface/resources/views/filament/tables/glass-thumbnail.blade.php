{{-- Thumbnail of a Glass model (FR-18, D-54-D-56): the glass and a row of equipment pictograms. Styles: resources/css/admin/components/glass-card.css --}}
<div class="glass-thumbnail">
    {!! $thumbnail->svg !!}

    @if ($thumbnail->pictograms !== [])
        <ul class="glass-thumbnail__pictograms">
            @foreach ($thumbnail->pictograms as $pictogram)
                <li class="glass-thumbnail__pictogram-item" x-tooltip="{ content: @js($pictogram['label']) }" aria-label="{{ $pictogram['label'] }}" role="img">
                    {!! $pictogram['svg'] !!}
                </li>
            @endforeach
        </ul>
    @endif
</div>
