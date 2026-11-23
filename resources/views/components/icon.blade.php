@props(['name' => 'circle-help', 'size' => 18])

<svg {{ $attributes->class(['icon'])->merge(['width' => $size, 'height' => $size, 'aria-hidden' => 'true', 'focusable' => 'false']) }} viewBox="0 0 24 24">
    <use href="#icon-{{ $name }}"></use>
</svg>
