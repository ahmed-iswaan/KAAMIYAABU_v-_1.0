<div class="container-xxl py-6">
    <div class="row g-6 mb-6">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div>
                            <h3 class="fw-bold mb-0">Vote Results (By Sub Consite)</h3>
                            <div class="text-muted small">Turnout = Yes + No + Invalid</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div wire:ignore>
                        <div id="vote_results_by_sub_consite" style="min-height: 420px;"></div>
                    </div>
                    <div id="vote-results-chart-data" class="d-none" data-chart='@json($chart ?? [])'></div>
                    <div id="vote-results-totals-data" class="d-none" data-totals='@json($totals ?? [])'></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div>
                            <h3 class="fw-bold mb-0">Total Votes</h3>
                            <div class="text-muted small">Yes / No / Invalid</div>
                        </div>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <div wire:ignore>
                        <div id="vote_results_totals_pie" style="min-height: 360px;"></div>
                    </div>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Yes Votes (Adam Azim)</span>
                            <span class="fw-semibold">{{ number_format(($totals['yes'] ?? 0)) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">No Votes (Ali Azim)</span>
                            <span class="fw-semibold">{{ number_format(($totals['no'] ?? 0)) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Invalid Votes</span>
                            <span class="fw-semibold">{{ number_format(($totals['invalid'] ?? 0)) }}</span>
                        </div>
                    </div>
                </div>
            </div>
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

    let barChart = null;
    let pieChart = null;

    function readBarData(){
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

    function readTotals(){
        try{
            const el = document.getElementById('vote-results-totals-data');
            if(!el) return { yes: 0, no: 0, invalid: 0 };
            const raw = el.dataset.totals;
            if(!raw) return { yes: 0, no: 0, invalid: 0 };
            const obj = JSON.parse(raw);
            return {
                yes: Number(obj.yes || 0),
                no: Number(obj.no || 0),
                invalid: Number(obj.invalid || 0),
            };
        }catch(e){
            return { yes: 0, no: 0, invalid: 0 };
        }
    }

    function buildBar(){
        const data = readBarData();
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

        try{ barChart && barChart.destroy(); }catch(e){}
        barChart = new ApexCharts(el, options);
        barChart.render();
    }

    function buildPie(){
        const totals = readTotals();
        const el = document.querySelector('#vote_results_totals_pie');
        if(!el) return;

        const options = {
            series: [totals.yes, totals.no, totals.invalid],
            labels: ['Yes Votes (Adam Azim)', 'No Votes (Ali Azim)', 'Invalid Votes'],
            chart: {
                type: 'pie',
                height: 360,
                toolbar: { show: false },
            },
            legend: { position: 'bottom' },
            colors: ['#f97316', '#16a34a', '#0ea5e9'],
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    const s = opts.w.config.series[opts.seriesIndex] || 0;
                    return s.toLocaleString() + ' (' + val.toFixed(1) + '%)';
                }
            },
            tooltip: {
                y: { formatter: (v) => Number(v||0).toLocaleString() }
            }
        };

        try{ pieChart && pieChart.destroy(); }catch(e){}
        pieChart = new ApexCharts(el, options);
        pieChart.render();
    }

    function rebuildAll(){
        buildBar();
        buildPie();
    }

    ensureApex(() => {
        rebuildAll();

        document.addEventListener('livewire:navigated', () => setTimeout(rebuildAll, 0));
        document.addEventListener('livewire:updated', () => setTimeout(rebuildAll, 0));

        // Fired from Livewire after saving results
        window.addEventListener('vote-results-updated', () => setTimeout(rebuildAll, 0));

        window.addEventListener('resize', () => {
            try{ barChart && barChart.resize(); }catch(e){}
            try{ pieChart && pieChart.resize(); }catch(e){}
        });
    });
})();
</script>
@endpush
