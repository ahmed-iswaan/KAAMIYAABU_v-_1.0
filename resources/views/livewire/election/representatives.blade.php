<div class="container-xxl py-6">
    <div class="card mb-6 rep-search-card">
        <div class="card-body rep-search-body">
            <!-- History button (top-right) -->
            <button type="button" class="btn btn-icon btn-light rep-history-btn rep-history-btn--top" wire:click="openHistory" title="History">
                <i class="ki-duotone ki-watch">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </button>

            <div class="rep-search-header">
                <div class="rep-search-title">
                    <h3 class="mb-1">Mark as Voted</h3>
                    <div class="text-muted small">Enter the 6 digits of the NID (prefix <strong>A</strong> is fixed), then search.</div>
                </div>

                <div class="rep-search-controls">
                    <div class="nid-boxes" wire:ignore>
                        <div class="nid-box nid-box--fixed">A</div>
                        <input class="nid-box" type="text" inputmode="numeric" maxlength="1" aria-label="NID digit 1" />
                        <input class="nid-box" type="text" inputmode="numeric" maxlength="1" aria-label="NID digit 2" />
                        <input class="nid-box" type="text" inputmode="numeric" maxlength="1" aria-label="NID digit 3" />
                        <input class="nid-box" type="text" inputmode="numeric" maxlength="1" aria-label="NID digit 4" />
                        <input class="nid-box" type="text" inputmode="numeric" maxlength="1" aria-label="NID digit 5" />
                        <input class="nid-box" type="text" inputmode="numeric" maxlength="1" aria-label="NID digit 6" />
                    </div>

                    <!-- Hidden Livewire-bound value (stores only the 6 digits) -->
                    <input type="hidden" wire:model.defer="searchNid" />

                    <button class="btn btn-primary rep-search-btn" type="button" wire:click="searchByNid">Search</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right-side history drawer -->
    @if($historyOpen)
        <div class="rep-drawer-overlay" wire:click="closeHistory"></div>
        <div class="rep-drawer" role="dialog" aria-modal="true" aria-label="Voted Representatives History">
            <div class="rep-drawer-header">
                <div>
                    <div class="fw-bold">History</div>
                    <div class="text-muted small">Recently marked as voted</div>
                </div>
                <button type="button" class="btn btn-icon btn-light" wire:click="closeHistory" title="Close">
                    <i class="ki-duotone ki-cross">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
            </div>

            <div class="rep-drawer-body">
                @if(($history ?? collect())->count() === 0)
                    <div class="text-muted">No history yet.</div>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($history as $item)
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $item->directory?->name ?? '—' }}</div>
                                            <div class="text-muted small">
                                                <span class="me-2">{{ $item->directory?->id_card_number ?? '' }}</span>
                                                @if($item->directory?->subConsite)
                                                    <span class="badge badge-light">{{ $item->directory->subConsite->code }}</span>
                                                @endif
                                            </div>
                                            <div class="text-muted small mt-1">
                                                Marked by <span class="fw-semibold">{{ $item->user?->name ?? '—' }}</span>
                                                @if($item->voted_at)
                                                    • {{ \Carbon\Carbon::parse($item->voted_at)->format('d M Y, h:i A') }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <button type="button" class="btn btn-sm btn-light-danger" wire:click="undoVoted('{{ $item->id }}')">
                                                Undo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if(($historyTotal ?? 0) > ($history ?? collect())->count())
                        <div class="mt-4">
                            <button type="button" class="btn btn-light w-100" wire:click="loadMoreHistory">More</button>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    @if($foundUser)
        @php
            $initials = '';
            if(trim($foundUser->name)){
                $parts = preg_split('/\s+/', trim($foundUser->name));
                $chars = array_map(function($p){ return substr($p,0,1); }, $parts);
                $initials = strtoupper(substr(implode('', $chars), 0, 2));
            }
        @endphp

        <div class="card shadow-sm rep-card">
            <div class="card-body rep-card-body">
                <div class="rep-card-header">
                    <div class="rep-avatar">
                        @if(!empty($foundUser->profile_picture))
                            <div class="symbol symbol-80px symbol-circle overflow-hidden">
                                <img src="{{ asset('storage/'.$foundUser->profile_picture) }}" alt="{{ $foundUser->name }}" class="w-80px h-80px object-fit-cover" />
                            </div>
                        @else
                            <div class="rep-initials">{{ $initials }}</div>
                        @endif
                    </div>

                    <div class="rep-headline">
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                            <h4 class="mb-0">{{ $foundUser->name }}</h4>
                            @if($alreadyVoted)
                                <span class="badge bg-success">Voted</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mt-2">
                            <span class="badge badge-light">{{ $foundUser->id_card_number }}</span>

                            @if(!empty($foundUser->gender))
                                @php($g = strtolower(trim($foundUser->gender)))
                                <span class="badge rep-gender-badge {{ $g === 'female' ? 'rep-gender--female' : ($g === 'male' ? 'rep-gender--male' : 'rep-gender--other') }} text-capitalize">
                                    {{ $foundUser->gender }}
                                </span>
                            @endif

                            @if($foundUser->subConsite)
                                <span class="badge badge-light">{{ $foundUser->subConsite->code }}</span>
                            @endif
                        </div>

                        <div class="rep-actions mt-4">
                            @if(!$alreadyVoted)
                                <button class="btn btn-success" wire:click="markAsVoted">Mark as Voted</button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Locations (like Directory page) -->
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="p-3 border rounded h-100">
                            <div class="text-muted small">
                                <div>{{ optional($foundUser->property)->name }}</div>
                                <div class="text-break">{{ $foundUser->street_address ?? '' }}{{ ($foundUser->street_address && $foundUser->address) ? ' / ' : '' }}{{ $foundUser->address ?? '' }}</div>
                                <div class="fw-semibold fs-7 text-muted">
                                    {{ $foundUser->island?->atoll?->code }}. {{ $foundUser->island?->name }}{{ $foundUser->country?->name ? ', '.$foundUser->country?->name : '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="col-12 col-md-6">
                        <div class="p-3 border rounded h-100">
                            <div class="text-muted small text-break">
                                {{ optional($foundUser->subConsite)->code ?? '' }}{{ optional($foundUser->subConsite)->name ? ' - '.optional($foundUser->subConsite)->name : '' }}
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    /* Use Craft/Metronic theme styles as much as possible.
       Keep only minimal component-specific CSS that theme doesn't provide. */

    /* Search header layout */
    .rep-search-body{position:relative;}
    .rep-search-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;}
    .rep-search-title{min-width:260px;flex:1 1 320px;}

    /* Right side: inputs + button. Reserve space so the top-right history button never overlaps. */
    .rep-search-controls{display:flex;align-items:center;gap:12px;margin-left:auto;flex:0 0 auto;padding-right:56px;}

    /* Desktop/tablet: keep history pinned top-right of the card */
    @media (min-width: 576px){
        .rep-history-btn--top{position:absolute;top:14px;right:14px;z-index:2;}
    }

    /* NID boxed inputs */
    .nid-boxes{display:flex;align-items:center;gap:10px;flex-wrap:nowrap;width:100%;max-width:420px;}
    .nid-box{
        width:46px;height:46px;
        border:1px solid var(--kt-input-border-color, #e4e6ef);
        border-radius:0.65rem;
        background:var(--kt-body-bg, #fff);
        text-align:center;
        font-weight:700;
        font-size:18px;
        color:var(--kt-text-gray-900, #181c32);
        outline:none;
    }
    .nid-box:focus{border-color:var(--kt-primary, #3e97ff); box-shadow:0 0 0 .25rem rgba(62,151,255,.15);}
    .nid-box--fixed{display:flex;align-items:center;justify-content:center;background:var(--kt-gray-100, #f9fafb);}

    /* Right-side drawer */
    .rep-drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9998;}
    .rep-drawer{position:fixed;top:0;right:0;height:100vh;width:420px;max-width:calc(100% - 24px);background:var(--kt-body-bg,#fff);z-index:9999;box-shadow:-10px 0 30px rgba(0,0,0,.12);display:flex;flex-direction:column;}
    .rep-drawer-header{padding:16px;border-bottom:1px solid var(--kt-border-color,#eef0f4);display:flex;align-items:center;justify-content:space-between;gap:12px;}
    .rep-drawer-body{padding:16px;overflow:auto;flex:1 1 auto;}

    /* Mobile: keep history button top-right, center inputs, keep in one row, button below centered */
    @media (max-width: 575.98px){
        .rep-history-btn--top{position:absolute;top:14px;right:14px;z-index:2;}

        /* keep header text left */
        .rep-search-header{justify-content:flex-start; text-align:left;}
        .rep-search-title{width:100%; padding-right:48px; text-align:left;}

        /* Center only the inputs/button area */
        .rep-search-controls{width:100%; flex-direction:column; align-items:center; margin-left:0; padding-right:0;}

        /* Fix NID boxes layout on mobile */
        .nid-boxes{
            width:100%;
            max-width:340px;
            justify-content:space-between;
            gap:6px;
        }
        .nid-box{
            width:40px;
            height:40px;
            font-size:16px;
            border-radius:.6rem;
            flex:0 0 auto;
        }

        .rep-search-btn{width:100%; max-width:220px;}
    }

    @media (max-width: 420px){
        .nid-boxes{max-width:320px;}
        .nid-box{width:36px;height:36px;font-size:15px;}
    }

    @media (max-width: 360px){
        .nid-boxes{max-width:300px;}
        .nid-box{width:34px;height:34px;font-size:14px;}
    }

    /* Representative card */
    .rep-card-body{padding:24px;}
    .rep-card-header{display:flex;flex-direction:column;align-items:center;text-align:center;}

    /* Ensure avatar is always round and centered */
    .rep-avatar{margin-top:4px;display:flex;justify-content:center;}
    .rep-avatar .symbol{border-radius:999px;}
    .rep-avatar img{border-radius:999px;object-fit:cover;}

    .rep-initials{
        width:80px;height:80px;border-radius:999px;
        display:flex;align-items:center;justify-content:center;
        font-weight:800;font-size:22px;
        background:var(--kt-primary-light, #eef6ff);
        color:var(--kt-primary, #3e97ff);
        border:1px solid var(--kt-primary-light, #eef6ff);
    }

    .rep-headline{width:100%;max-width:720px;margin-top:14px;}

    /* Center the action button */
    .rep-actions{display:flex;justify-content:center;}
    .rep-actions .btn{min-width:180px;}

    /* Slightly tighter on small screens */
    @media (max-width: 575.98px){
        .rep-card-body{padding:18px;}
        .rep-actions .btn{width:100%;max-width:240px;}
    }

    /* Gender badges */
    .rep-gender-badge{border:0;font-weight:600;}
    .rep-gender--female{background:var(--kt-danger-light, #ffe6f1); color:var(--kt-danger, #f1416c);}
    .rep-gender--male{background:var(--kt-primary-light, #eef6ff); color:var(--kt-primary, #3e97ff);}
    .rep-gender--other{background:var(--kt-gray-200, #f1f1f2); color:var(--kt-text-gray-700, #5e6278);}
</style>
@endpush

@push('scripts')
<script>
(function(){
    function initNidBoxes(root){
        if(!root) return;

        const inputs = Array.from(root.querySelectorAll('input.nid-box'));
        const hidden = root.parentElement.querySelector('input[type="hidden"][wire\\:model\\.defer="searchNid"], input[type="hidden"][wire\\:model="searchNid"]')
            || root.parentElement.querySelector('input[type="hidden"]');

        function syncHidden(){
            const value = inputs.map(i => (i.value || '').replace(/\D/g,'')).join('').slice(0,6);
            if(hidden){
                hidden.value = value;
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        function clearBoxes(){
            inputs.forEach(i => i.value = '');
            syncHidden();
            if(inputs[0]) inputs[0].focus();
        }

        inputs.forEach((input, idx) => {
            input.autocomplete = 'off';

            input.addEventListener('input', () => {
                input.value = (input.value || '').replace(/\D/g,'').slice(0,1);
                syncHidden();
                if(input.value && inputs[idx+1]) inputs[idx+1].focus();
            });

            input.addEventListener('keydown', (e) => {
                if(e.key === 'Backspace' && !input.value && inputs[idx-1]){
                    inputs[idx-1].focus();
                }
                if(e.key === 'ArrowLeft' && inputs[idx-1]) inputs[idx-1].focus();
                if(e.key === 'ArrowRight' && inputs[idx+1]) inputs[idx+1].focus();
                if(e.key === 'Enter'){
                    // allow enter-to-search from any box
                    const btn = root.parentElement.querySelector('button[wire\\:click="searchByNid"]');
                    if(btn) btn.click();
                }
            });

            input.addEventListener('paste', (e) => {
                const text = (e.clipboardData || window.clipboardData).getData('text') || '';
                const digits = text.replace(/\D/g,'').slice(0,6);
                if(!digits) return;
                e.preventDefault();
                digits.split('').forEach((d, i) => {
                    if(inputs[i]) inputs[i].value = d;
                });
                syncHidden();
                const next = inputs[Math.min(digits.length, inputs.length-1)];
                if(next) next.focus();
            });
        });

        // Reset handler from Livewire
        window.addEventListener('nid:reset', clearBoxes);

        // Initial focus to first digit box
        if(inputs[0] && !inputs.some(i => i.value)) inputs[0].focus();
    }

    document.addEventListener('livewire:navigated', () => {
        initNidBoxes(document.querySelector('.nid-boxes'));
    });

    document.addEventListener('DOMContentLoaded', () => {
        initNidBoxes(document.querySelector('.nid-boxes'));
    });
})();
</script>
@endpush
