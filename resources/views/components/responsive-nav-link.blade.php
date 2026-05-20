@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-2xl bg-cyan-50 px-4 py-3 text-start text-base font-semibold text-slate-950 shadow-sm shadow-cyan-100/80 transition duration-150 ease-in-out'
            : 'block w-full rounded-2xl px-4 py-3 text-start text-base font-medium text-slate-600 transition duration-150 ease-in-out hover:bg-white/70 hover:text-slate-900 focus:outline-none focus:bg-white/80 focus:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
