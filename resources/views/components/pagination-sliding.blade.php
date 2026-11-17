@if ($paginator->hasPages())
    @php
        $current = (int) $paginator->currentPage();
        $last = (int) $paginator->lastPage();

        // Jendela 3 nomor halaman sliding
        // Halaman 1 -> (1)(2)(3)(Next) [jika $last >= 3]
        // Halaman 2 -> (Back)(1)(2)(3)(Next)
        // Halaman 3 -> (Back)(2)(3)(4)(Next)
        // Back tidak muncul di halaman 1; Next tidak muncul di halaman terakhir.
        // Halaman aktif diberi highlight marun.
        if ($last <= 3) {
            $start = 1;
            $end = $last;
        } else {
            if ($current <= 2) {
                $start = 1;
                $end = 3;
            } elseif ($current >= $last - 1) {
                $start = $last - 2;
                $end = $last;
            } else {
                $start = $current - 1;
                $end = $current + 1;
            }
        }
    @endphp

    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center space-x-1.5">
        {{-- Back Button: tidak muncul di halaman 1 --}}
        @if (! $paginator->onFirstPage())
            <a href="{{ $paginator->appends(request()->query())->previousPageUrl() }}" rel="prev"
               class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 flex items-center space-x-1 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Back</span>
            </a>
        @endif

        {{-- Jendela 3 Nomor Halaman --}}
        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $current)
                <span aria-current="page"
                      class="min-w-[32px] h-8 px-2 rounded-lg bg-[#8b0000] text-white flex items-center justify-center text-xs font-bold shadow-xs">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->appends(request()->query())->url($page) }}"
                   class="min-w-[32px] h-8 px-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 flex items-center justify-center text-xs font-semibold transition-colors">
                    {{ $page }}
                </a>
            @endif
        @endfor

        {{-- Next Button: tidak muncul di halaman terakhir --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->appends(request()->query())->nextPageUrl() }}" rel="next"
               class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 flex items-center space-x-1 transition-colors">
                <span>Next</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @endif
    </nav>
@endif
