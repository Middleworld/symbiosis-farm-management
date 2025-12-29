@if ($paginator->hasPages())
    <!-- CUSTOM SIMPLE PAGINATION v2.0 - <?php echo time(); ?> -->
    <nav>
        <div class="simple-pagination">
            <div class="pagination-info">
                Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
            </div>
            
            <div class="pagination-links">
                {{-- Previous Page Link --}}
                @if (!$paginator->onFirstPage())
                    <a href="{{ $paginator->previousPageUrl() }}" class="page-nav">‹ Prev</a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="page-dots">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="page-num active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="page-num">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="page-nav">Next ›</a>
                @endif
            </div>
        </div>
        
        <style>
        .simple-pagination {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .pagination-info {
            color: #6c757d;
            font-size: 13px;
        }
        
        .pagination-links {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .page-num,
        .page-nav,
        .page-dots {
            padding: 4px 8px;
            text-decoration: none;
            color: #0d6efd;
            font-size: 14px;
            line-height: 1;
            border-radius: 3px;
            transition: background-color 0.15s;
        }
        
        .page-num:hover,
        .page-nav:hover {
            background-color: #e9ecef;
            text-decoration: none;
        }
        
        .page-num.active {
            background-color: #0d6efd;
            color: white;
            font-weight: 600;
        }
        
        .page-dots {
            color: #6c757d;
            cursor: default;
        }
        </style>
    </nav>
@endif
