{{-- Thumbnail of a Glass model (FR-18, D-54-D-56), fed with Catalogue's `Thumbnail` DTO: the glass and, unless `:pictograms="false"`, a row of equipment pictograms. Offers draws it too. Styles: resources/css/admin/components/glass-card.css --}}
@props(['thumbnail', 'pictograms' => true])

<div {{ $attributes->class('glass-thumbnail') }}>
    <x-catalogue::glass-shape :shape="$thumbnail->shape" :mirrored="$thumbnail->mirrored" :fill="$thumbnail->fill" :opacity="$thumbnail->opacity" />

    @if ($pictograms)
        {{-- Always drawn, so a model without pictograms keeps the same picture area. --}}
        <ul class="glass-thumbnail__pictograms">
            @foreach ($thumbnail->pictograms as $item)
                <li class="glass-thumbnail__pictogram-item" x-tooltip="{ content: @js($item['label']) }" aria-label="{{ $item['label'] }}" role="img">
                    <x-catalogue::glass-pictogram :pictogram="$item['pictogram']" />
                </li>
            @endforeach
        </ul>
    @endif
</div>
