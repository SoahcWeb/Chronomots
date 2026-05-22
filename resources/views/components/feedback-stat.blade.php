@props([
    'title',
    'value',
    'description' => null,
    'tone' => 'sky',
    'reveal' => true,
    'score' => false,
])

@php
    $tones = [
        'sky' => [
            'background' => 'bg-gradient-to-br from-cyan-100 via-white to-sky-50',
            'text' => 'text-cyan-700',
        ],
        'success' => [
            'background' => 'bg-gradient-to-br from-emerald-100 via-white to-lime-50',
            'text' => 'text-emerald-700',
        ],
        'warning' => [
            'background' => 'bg-gradient-to-br from-orange-100 via-white to-amber-50',
            'text' => 'text-orange-700',
        ],
        'plum' => [
            'background' => 'bg-gradient-to-br from-violet-100 via-white to-indigo-50',
            'text' => 'text-violet-700',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['sky'];
@endphp

<article
    {{ $attributes->class([
        'chronomots-feedback-card rounded-[1.75rem] p-5 shadow-sm',
        $toneClasses['background'],
        $score ? 'chronomots-score-burst chronomots-score-burst--spotlight' : null,
    ]) }}
    @if ($reveal) data-feedback-reveal @endif
    @if ($score) data-feedback-score @endif
>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] {{ $toneClasses['text'] }}">{{ $title }}</p>
    <p class="chronomots-score-value mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $value }}</p>

    @if ($description)
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif

    {{ $slot }}
</article>
