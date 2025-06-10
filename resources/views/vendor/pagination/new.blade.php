@if ($paginator->hasPages())
    <div class="dataTables_paginate paging_simple_numbers" id="kt_table_users_paginate">
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="paginate_button page-item previous disabled" id="kt_table_users_previous" aria-label="@lang('pagination.previous')">
                    <span class="page-link">
                        <i class="previous"></i>
                    </span>
                </li>
            @else
                <li class="paginate_button page-item previous" id="kt_table_users_previous" aria-label="@lang('pagination.previous')">
                    <a href="#" wire:click="previousPage" aria-controls="kt_table_users" data-dt-idx="0" tabindex="0" class="page-link">
                        <i class="previous"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="paginate_button page-item disabled">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="paginate_button page-item active">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="paginate_button page-item">
                                <a href="#" wire:click="gotoPage({{ $page }})" aria-controls="kt_table_users" data-dt-idx="{{ $page }}" tabindex="0" class="page-link">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="paginate_button page-item next" id="kt_table_users_next">
                    <a href="#" wire:click="nextPage" aria-controls="kt_table_users" data-dt-idx="4" tabindex="0" class="page-link" aria-label="@lang('pagination.next')">
                        <i class="next"></i>
                    </a>
                </li>
            @else
                <li class="paginate_button page-item next disabled" id="kt_table_users_next" aria-label="@lang('pagination.next')">
                    <span class="page-link">
                        <i class="next"></i>
                    </span>
                </li>
            @endif
        </ul>
    </div>
@endif
