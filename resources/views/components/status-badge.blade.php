@props([
    'type' => 'default', // 'final-assy', 'pre-assy', 'defect', 'warning', 'success', 'default'
])

@php
    $classes = match($type) {
        'final-assy' => 'bg-teal-50 text-final-assy border border-teal-200/80',
        'pre-assy'   => 'bg-slate-100 text-pre-assy border border-slate-300/80',
        'defect'     => 'bg-rose-50 text-final-assy-defect border border-rose-200/80',
        'warning'    => 'bg-amber-50 text-pre-assy-warning border border-amber-200/80',
        'brand'      => 'bg-red-50 text-brand border border-red-200/80',
        'success'    => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
        default      => 'bg-gray-100 text-gray-800 border border-gray-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-extrabold tracking-wider uppercase font-mono {$classes}"]) }}>
    {{ $slot }}
</span>
