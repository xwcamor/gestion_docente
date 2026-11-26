@props(['name' => 'circle-help', 'size' => 18])

@php
    // Alias habituales de Lucide que conservan una forma equivalente en el sprite.
    $iconAliases = [
        'edit-3' => 'pencil',
        'check-circle' => 'circle-check',
        'external-link' => 'arrow-up-right',
    ];

    $iconName = $iconAliases[$name] ?? $name;
@endphp

<svg {{ $attributes->class(['icon'])->merge(['width' => $size, 'height' => $size, 'aria-hidden' => 'true', 'focusable' => 'false']) }} viewBox="0 0 24 24">
    <use href="#icon-{{ $iconName }}"></use>
</svg>
