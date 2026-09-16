@props([
    'disabled' => false,
    'label' => null,
    'required' => false,
    'error' => null,
])

<div class="w-full space-y-1.5">
    @if($label)
        <label {{ $attributes->has('id') ? 'for="'.$attributes->get('id').'"' : '' }} class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
            {{ $label }}
            @if($required)
                <span class="text-brand">*</span>
            @endif
        </label>
    @endif

    <input 
        {{ $disabled ? 'disabled' : '' }} 
        {{ $attributes->merge([
            'class' => 'w-full bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed transition-all duration-150'
        ]) }}
    >

    @if($error)
        <p class="text-[11px] font-semibold text-brand">{{ $error }}</p>
    @endif
</div>
