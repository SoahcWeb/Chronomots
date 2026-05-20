<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full border border-transparent bg-slate-950 px-5 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-white shadow-lg shadow-cyan-200/30 transition duration-150 ease-in-out hover:-translate-y-0.5 hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 active:bg-slate-950']) }}>
    {{ $slot }}
</button>
