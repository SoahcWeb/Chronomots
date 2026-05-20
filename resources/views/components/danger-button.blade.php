<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full border border-transparent bg-orange-500 px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-white shadow-lg shadow-orange-200/50 transition duration-150 ease-in-out hover:bg-orange-400 active:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
