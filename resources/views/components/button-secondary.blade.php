@props([
    'type' => 'button',
])

<button 
    type="{{ $type }}" 
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-border focus:outline-none focus:ring-2 focus:ring-gray-300 active:scale-95 transition-all duration-150 disabled:opacity-50 disabled:pointer-events-none'
    ]) }}
>
    {{ $slot }}
</button>
