@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border border-cyan-200/80 bg-white/90 px-4 py-2 text-sm font-semibold leading-5 text-slate-950 shadow-md shadow-cyan-100/70 ring-1 ring-white/70 backdrop-blur-sm'
            : 'inline-flex items-center rounded-full border border-transparent px-4 py-2 text-sm font-medium leading-5 text-slate-600 hover:-translate-y-0.5 hover:border-cyan-100 hover:bg-white/80 hover:text-slate-950 hover:shadow-sm hover:shadow-cyan-100/80 focus:outline-none focus:border-cyan-200 focus:bg-white/85 focus:text-slate-950';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
