@props([
    'type' => 'button',
])

<button 
    type="{{ $type }}" 
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-brand hover:bg-brand-active focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 active:scale-95 transition-all duration-150 shadow-sm shadow-brand/20 disabled:opacity-50 disabled:pointer-events-none'
    ]) }}
>
    {{ $slot }}
</button>
