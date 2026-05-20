@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 text-slate-900 shadow-sm shadow-slate-100 transition duration-150 placeholder:text-slate-400 focus:border-cyan-300 focus:ring-cyan-300']) }}>
