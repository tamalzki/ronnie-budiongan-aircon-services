{{-- Unified page title row — pass <x-slot name="actions"> for right-side buttons --}}
@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'marginClass' => 'mb-3',
])

<div {{ $attributes->merge(['class' => 'd-flex justify-content-between align-items-center flex-wrap gap-2 ' . $marginClass]) }}>
    <div class="min-w-0">
        <h4 class="app-page-title mb-0">
            @if($icon)
                <i class="bi {{ $icon }} text-primary me-1"></i>
            @endif
            {{ $title }}
        </h4>
        @if($subtitle)
            <p class="app-page-subtitle mb-0 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
