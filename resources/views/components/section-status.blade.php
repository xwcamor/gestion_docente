@props([
    'status' => 'pending',
    'label' => null,
])

@php
    $states = [
        'complete' => ['label' => 'Completado', 'symbol' => '✓'],
        'partial' => ['label' => 'En proceso', 'symbol' => '◷'],
        'pending' => ['label' => 'Pendiente', 'symbol' => '•'],
    ];
    $state = $states[$status] ?? $states['pending'];
@endphp

<span {{ $attributes->class(['section-status', 'section-status-'.$status]) }}>
    <span class="section-status-symbol" aria-hidden="true">{{ $state['symbol'] }}</span>
    {{ $label ?? $state['label'] }}
</span>
