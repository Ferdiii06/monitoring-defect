@props([
    'label' => '',
    'value' => '0',
    'valueId' => null,
    'sublabel' => null,
    'iconBg' => 'bg-red-50',
    'iconBorder' => 'border-red-100',
    'iconColor' => 'text-brand',
])

<div {{ $attributes->merge(['class' => 'group bg-surface border border-border rounded-2xl p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] hover:shadow-[0_8px_25px_-5px_rgba(0,0,0,0.08)] hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between']) }}>
    <div class="flex items-center space-x-4">
        @if(isset($icon))
            <div class="w-12 h-12 rounded-xl {{ $iconBg }} border {{ $iconBorder }} flex items-center justify-center {{ $iconColor }} shrink-0 transition-transform duration-200 group-hover:scale-105">
                {{ $icon }}
            </div>
        @endif

        <div>
            <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $label }}</span>
            <span @if($valueId) id="{{ $valueId }}" @endif class="block text-2xl font-black text-gray-950 font-mono tabular-nums mt-0.5 leading-none tracking-tight">
                {{ $slot->isNotEmpty() ? $slot : $value }}
            </span>

            @if(isset($trend))
                <div class="flex items-center space-x-1.5 mt-1.5">
                    {{ $trend }}
                </div>
            @elseif($sublabel)
                <span class="block text-[10px] text-gray-400 font-medium mt-1">{{ $sublabel }}</span>
            @endif
        </div>
    </div>
</div>
