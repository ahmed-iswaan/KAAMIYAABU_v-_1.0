<div class="container-xxl py-6">
    <div class="card mb-6">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div>
                    <h3 class="fw-bold mb-0">Vote Results (By Sub Consite)</h3>
                    <div class="text-muted small">Turnout = Yes + No + Invalid</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="vote_results_by_sub_consite" style="min-height: 420px;"></div>
            <div id="vote-results-chart-data" class="d-none" data-chart='@json($chart ?? [])'></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Vote Results Entry</h3>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Sub Consite</label>
                    <select class="form-select" wire:model.live="subConsiteId">
                        <option value="">Select sub consite</option>
                        @foreach($subConsites as $sc)
                            <option value="{{ $sc->id }}">{{ $sc->code }}{{ $sc->name ? ' - '.$sc->name : '' }}</option>
                        @endforeach
                    </select>
                    @error('subConsiteId')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-8">
                    <div class="alert alert-info mb-0">
                        <div class="d-flex flex-wrap gap-4 justify-content-between">
                            <div>
                                <div class="text-muted small">Turnout</div>
                                <div class="fw-bold fs-3 {{ $turnoutOk ? 'text-gray-900' : 'text-danger' }}">{{ number_format($turnout) }}</div>
                                <div class="text-muted small">Yes + No + Invalid</div>
                            </div>
                            <div>
                                <div class="text-muted small">Eligible</div>
                                <div class="fw-bold fs-3">{{ number_format($totalEligibleVoters ?? 0) }}</div>
                            </div>
                            <div>
                                <div class="text-muted small">Check</div>
                                @if($turnoutOk)
                                    <span class="badge badge-light-success">OK</span>
                                @else
                                    <span class="badge badge-light-danger">Turnout exceeds eligible</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Total Eligible Voters</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="totalEligibleVoters" />
                    @error('totalEligibleVoters')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Yes Votes (Adam Azim)</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="yesVotes" />
                    @error('yesVotes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">No Votes (Ali Azim)</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="noVotes" />
                    @error('noVotes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Invalid Votes</label>
                    <input type="number" min="0" class="form-control" wire:model.defer="invalidVotes" />
                    @error('invalidVotes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-end mt-6">
                <button type="button" class="btn btn-primary" wire:click="save">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
    function ensureApex(cb){
        if(window.ApexCharts) return cb();
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
        s.onload = cb;
        document.head.appendChild(s);
    }

    let chart = null;

    function readData(){
        try{
            const el = document.getElementById('vote-results-chart-data');
            if(!el) return { labels: [], eligible: [], yes: [], no: [], invalid: [], turnout: [] };
            const raw = el.dataset.chart;
            if(!raw) return { labels: [], eligible: [], yes: [], no: [], invalid: [], turnout: [] };
            return JSON.parse(raw);
        }catch(e){
            return { labels: [], eligible: [], yes: [], no: [], invalid: [], turnout: [] };
        }
    }

    function build(){
        const data = readData();
        const el = document.querySelector('#vote_results_by_sub_consite');
        if(!el) return;

        const labels = Array.isArray(data.labels) ? data.labels : [];
        const eligible = Array.isArray(data.eligible) ? data.eligible.map(n => Number(n||0)) : [];
        const yes = Array.isArray(data.yes) ? data.yes.map(n => Number(n||0)) : [];
        const no = Array.isArray(data.no) ? data.no.map(n => Number(n||0)) : [];
        const invalid = Array.isArray(data.invalid) ? data.invalid.map(n => Number(n||0)) : [];
        const turnout = Array.isArray(data.turnout) ? data.turnout.map(n => Number(n||0)) : [];

        const options = {
            series: [
                { name: 'Eligible', data: eligible },
                { name: 'Yes Votes (Adam Azim)', data: yes },
                { name: 'No Votes (Ali Azim)', data: no },
                { name: 'Invalid Votes', data: invalid },
                { name: 'Turnout', data: turnout },
            ],
            chart: {
                type: 'bar',
                height: 420,
                stacked: true,
                toolbar: { show: false },
                animations: { enabled: true }
            },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            dataLabels: { enabled: true },
            xaxis: { categories: labels },
            legend: { position: 'bottom' },
            colors: ['#1e5a7a', '#f97316', '#16a34a', '#0ea5e9', '#a855f7'],
            tooltip: { y: { formatter: (val) => Number(val).toLocaleString() } },
            yaxis: { labels: { formatter: (val) => Number(val).toLocaleString() } },
        };

        try{ chart && chart.destroy(); }catch(e){}
        chart = new ApexCharts(el, options);
        chart.render();
    }

    ensureApex(() => {
        build();

        document.addEventListener('livewire:navigated', () => setTimeout(build, 0));
        document.addEventListener('livewire:updated', () => setTimeout(build, 0));

        window.addEventListener('resize', () => {
            try{ chart && chart.resize(); }catch(e){}
        });
    });
})();
</script>
@endpush
