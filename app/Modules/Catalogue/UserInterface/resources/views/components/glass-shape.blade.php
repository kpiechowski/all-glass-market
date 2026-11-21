{{-- The outline of one glass shape (FR-15, FR-16): only shipped geometry, a validated fill and an integer opacity. Strokes use currentColor, so the theme colours the outline. Styles: resources/css/admin/components/glass-card.css --}}
@props(['shape', 'mirrored' => false, 'fill', 'opacity'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="{{ \App\Modules\Catalogue\Domain\Enums\GlassShape::VIEW_BOX }}" class="glass-thumbnail__shape" aria-hidden="true">
    <g @if ($mirrored) transform="translate(100 0) scale(-1 1)" @endif>
        <path d="{{ $shape->outline() }}" fill="{{ $fill }}" fill-opacity="{{ $opacity / 100 }}" stroke="currentColor" stroke-width="2" stroke-linejoin="round" @if ($shape === \App\Modules\Catalogue\Domain\Enums\GlassShape::Unknown) stroke-dasharray="5 4" @endif />
    </g>
</svg>
