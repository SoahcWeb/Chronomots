@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50/80 px-4 py-2 text-sm font-semibold leading-5 text-slate-950 shadow-sm shadow-cyan-100/80 transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full border border-transparent px-4 py-2 text-sm font-medium leading-5 text-slate-600 transition duration-150 ease-in-out hover:border-slate-200 hover:bg-white/70 hover:text-slate-900 focus:outline-none focus:border-cyan-200 focus:bg-white/80 focus:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
