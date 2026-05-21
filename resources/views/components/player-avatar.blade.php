@props([
    'avatar',
    'title' => null,
    'subtitle' => null,
    'size' => 'md',
    'stacked' => false,
])

@php
    $sizeClass = match ($size) {
        'sm' => 'chronomots-avatar--sm',
        'lg' => 'chronomots-avatar--lg',
        default => 'chronomots-avatar--md',
    };
@endphp

<div class="flex {{ $stacked ? 'flex-col items-center text-center' : 'items-center gap-3' }}">
    <div class="chronomots-avatar chronomots-avatar--{{ $avatar['tone'] }} {{ $sizeClass }}">
        <span>{{ $avatar['mark'] }}</span>
    </div>

    @if ($title || $subtitle)
        <div class="{{ $stacked ? 'mt-3' : '' }}">
            @if ($title)
                <p class="text-sm font-black text-slate-950">{{ $title }}</p>
            @endif
            @if ($subtitle)
                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
</div>
