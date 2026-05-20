<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/85 px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700 shadow-sm transition duration-150 ease-in-out hover:border-cyan-200 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
