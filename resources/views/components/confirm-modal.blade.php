@props([
    'name', // Alpine.js boolean variable name (e.g. 'logoutModal')
    'title' => 'Konfirmasi Tindakan',
    'message' => 'Apakah Anda yakin ingin melanjutkan?',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'confirmAction' => null, // form action url
    'method' => 'POST',
])

<div x-show="{{ $name }}" 
     x-cloak 
     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur Dim -->
    <div x-show="{{ $name }}"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 backdrop-blur-none"
         x-transition:enter-end="opacity-100 backdrop-blur-sm"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 backdrop-blur-sm"
         x-transition:leave-end="opacity-0 backdrop-blur-none"
         class="fixed inset-0 bg-gray-900/50 transition-opacity"
         @click="{{ $name }} = false"></div>

    <!-- Modal Dialog Content -->
    <div x-show="{{ $name }}"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         class="relative bg-surface rounded-2xl max-w-sm sm:max-w-md w-full p-6 sm:p-7 shadow-2xl border border-border z-10 transform" 
         @click.away="{{ $name }} = false">
        
        <div class="flex items-start space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-brand shrink-0 shadow-xs">
                @if(isset($icon))
                    {{ $icon }}
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                @endif
            </div>
            <div class="flex-1">
                <h3 class="text-base font-bold text-gray-900 leading-snug">{{ $title }}</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $message }}</p>
            </div>
        </div>

        @if($slot->isNotEmpty())
            <div class="mt-4 text-xs text-gray-600">
                {{ $slot }}
            </div>
        @endif

        <div class="mt-6 pt-4 border-t border-border flex items-center justify-end space-x-3">
            <button type="button" 
                    @click="{{ $name }} = false" 
                    class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-border rounded-xl transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300 active:scale-95">
                {{ $cancelText }}
            </button>

            @if($confirmAction)
                <form method="POST" action="{{ $confirmAction }}" class="inline">
                    @csrf
                    @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                        @method($method)
                    @endif
                    <button type="submit" 
                            class="px-4 py-2.5 text-xs font-bold text-white bg-brand hover:bg-brand-active rounded-xl transition-all duration-150 shadow-sm shadow-brand/20 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 active:scale-95">
                        {{ $confirmText }}
                    </button>
                </form>
            @else
                <button type="button" 
                        @click="$dispatch('confirmed-{{ $name }}'); {{ $name }} = false"
                        class="px-4 py-2.5 text-xs font-bold text-white bg-brand hover:bg-brand-active rounded-xl transition-all duration-150 shadow-sm shadow-brand/20 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 active:scale-95">
                    {{ $confirmText }}
                </button>
            @endif
        </div>
    </div>
</div>
