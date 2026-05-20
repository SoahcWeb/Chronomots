@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-2xl border border-cyan-100/80 bg-white/90 px-4 py-3 text-start text-base font-semibold text-slate-950 shadow-md shadow-cyan-100/70 ring-1 ring-white/70 backdrop-blur-sm'
            : 'block w-full rounded-2xl border border-transparent px-4 py-3 text-start text-base font-medium text-slate-600 hover:bg-white/75 hover:text-slate-950 hover:shadow-sm hover:shadow-cyan-100/70 focus:outline-none focus:border-cyan-200 focus:bg-white/85 focus:text-slate-950';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
