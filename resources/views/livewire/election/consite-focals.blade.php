<div class="container-xxl py-6">
    <div class="card mb-6">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-4">
                <div>
                    <h3 class="mb-1">Consites Focals</h3>
                    <div class="text-muted small">List of directories not yet marked as voted (only your assigned sub consites).</div>
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div style="min-width:220px">
                        <select class="form-select" wire:model.live="subConsiteId">
                            <option value="">All Sub Consites</option>
                            @foreach(($subConsites ?? []) as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->code }}{{ $sc->name ? ' - '.$sc->name : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width:260px">
                        <input type="text" class="form-control" placeholder="Search name, NID, address or phone" wire:model.live.debounce.400ms="search" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <!-- Mobile card list -->
            <div class="d-block d-md-none p-4">
                <div class="d-flex flex-column gap-4">
                    @forelse($directories as $d)
                        @php
                            $initials = '';
                            if(trim((string)($d->name ?? ''))){
                                $parts = preg_split('/\s+/', trim((string)$d->name));
                                $chars = array_map(fn($p) => mb_substr($p,0,1), $parts);
                                $initials = mb_strtoupper(mb_substr(implode('', $chars), 0, 2));
                            }
                            $perm = trim(($d->street_address ?? '') . (($d->street_address && $d->address) ? ' / ' : '') . ($d->address ?? ''));
                            $cur = trim(($d->current_street_address ?? '') . (($d->current_street_address && $d->current_address) ? ' / ' : '') . ($d->current_address ?? ''));
                            $phones = $d->phones;
                            if (is_array($phones)) { $phones = implode(', ', array_filter($phones)); }
                            $fp = $d->final_pledge_status ?? null;
                            $fpLabel = $fp ? str_replace('_',' ', (string)$fp) : 'Pending';
                            $fpClass = match($fp){
                                'strong_yes','yes' => 'badge-light-success',
                                'neutral' => 'badge-light-warning',
                                'no','strong_no' => 'badge-light-danger',
                                default => 'badge-light'
                            };
                        @endphp

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="symbol symbol-50px symbol-circle flex-shrink-0">
                                        @if(!empty($d->profile_picture))
                                            <img src="{{ asset('storage/'.$d->profile_picture) }}" alt="{{ $d->name }}" class="w-50px h-50px object-fit-cover" />
                                        @else
                                            <span class="symbol-label bg-light-primary text-primary fw-bold">{{ $initials ?: '—' }}</span>
                                        @endif
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="fw-bold">{{ $d->name }}</div>
                                                <div class="text-muted small mt-1">
                                                    <span class="badge badge-light me-2">{{ $d->id_card_number }}</span>
                                                    @if($d->subConsite)
                                                        <span class="badge badge-light">{{ $d->subConsite->code }}</span>
                                                    @endif
                                                     <span class="badge badge-light me-2">{{ !empty($phones) ? $phones : '—' }}</span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge {{ $fpClass }} text-capitalize">{{ $fpLabel }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                            
                                            <div class="text-muted small">{{ $perm !== '' ? $perm : '—' }}</div>
                                        </div>

                                        <!-- <div class="mt-3">
                                            <div class="text-muted fw-semibold small">Current</div>
                                            <div class="text-muted small">{{ $cur !== '' ? $cur : '—' }}</div>
                                        </div> -->

                                        <!-- <div class="mt-3">
                                            <div class="text-muted fw-semibold small">Phones</div>
                                            <div class="text-muted small text-break">{{ !empty($phones) ? $phones : '—' }}</div>
                                        </div> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-10">No not-voted directories found.</div>
                    @endforelse
                </div>
            </div>

            <!-- Desktop table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4 mb-0">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th class="ps-4">Profile</th>
                            <th>Name</th>
                            <th>NID</th>
                            <th>Sub Consite</th>
                            <th>Final Pledge</th>
                            <th>Permanent</th>
                            <th>Current</th>
                            <th class="pe-4">Phones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($directories as $d)
                            @php
                                $initials = '';
                                if(trim((string)($d->name ?? ''))){
                                    $parts = preg_split('/\s+/', trim((string)$d->name));
                                    $chars = array_map(fn($p) => mb_substr($p,0,1), $parts);
                                    $initials = mb_strtoupper(mb_substr(implode('', $chars), 0, 2));
                                }
                                $fp = $d->final_pledge_status ?? null;
                                $fpLabel = $fp ? str_replace('_',' ', (string)$fp) : 'Pending';
                                $fpClass = match($fp){
                                    'strong_yes','yes' => 'badge-light-success',
                                    'neutral' => 'badge-light-warning',
                                    'no','strong_no' => 'badge-light-danger',
                                    default => 'badge-light'
                                };
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="symbol symbol-40px symbol-circle">
                                        @if(!empty($d->profile_picture))
                                            <img src="{{ asset('storage/'.$d->profile_picture) }}" alt="{{ $d->name }}" class="w-40px h-40px object-fit-cover" />
                                        @else
                                            <span class="symbol-label bg-light-primary text-primary fw-bold">{{ $initials ?: '—' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $d->name }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light">{{ $d->id_card_number }}</span>
                                </td>
                                <td>
                                    @if($d->subConsite)
                                        <span class="badge badge-light">{{ $d->subConsite->code }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $fpClass }} text-capitalize">{{ $fpLabel }}</span>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        @php
                                            $perm = trim(($d->street_address ?? '') . (($d->street_address && $d->address) ? ' / ' : '') . ($d->address ?? ''));
                                        @endphp
                                        {{ $perm !== '' ? $perm : '—' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        @php
                                            $cur = trim(($d->current_street_address ?? '') . (($d->current_street_address && $d->current_address) ? ' / ' : '') . ($d->current_address ?? ''));
                                        @endphp
                                        {{ $cur !== '' ? $cur : '—' }}
                                    </div>
                                </td>
                                <td class="pe-4">
                                    <div class="text-muted small text-break">
                                        @php
                                            $phones = $d->phones;
                                            if (is_array($phones)) {
                                                $phones = implode(', ', array_filter($phones));
                                            }
                                        @endphp
                                        {{ !empty($phones) ? $phones : '—' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-muted">No not-voted directories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(method_exists($directories, 'links'))
            <div class="card-footer">
                {{ $directories->links() }}
            </div>
        @endif
    </div>
</div>
